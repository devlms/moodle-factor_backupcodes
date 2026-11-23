<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace factor_backupcodes;

/**
 * Tests for backup codes.
 *
 * @package    factor_backupcodes
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \factor_backupcodes\factor
 */
final class factor_test extends \advanced_testcase {
    /**
     * The enabled factor, for a new logged-in user.
     *
     * @return factor
     */
    private function factor(): factor {
        $this->resetAfterTest();
        set_config('enabled', 1, 'factor_backupcodes');
        $this->setUser($this->getDataGenerator()->create_user());
        return \tool_mfa\plugininfo\factor::get_factor('backupcodes');
    }

    /**
     * Set up codes for the current user.
     *
     * @param factor $factor
     * @return string[] the plain codes
     */
    private function make_codes(factor $factor): array {
        $codes = factor::generate_codes();
        $factor->setup_user_factor((object)['codes' => implode(' ', $codes), 'saved' => 1]);
        return $codes;
    }

    public function test_codes_are_ten_readable_unique_codes(): void {
        $codes = factor::generate_codes();
        $this->assertCount(10, $codes);
        $this->assertCount(10, array_unique($codes));
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[a-hj-km-np-z2-9]{5}-[a-hj-km-np-z2-9]{5}$/', $code);
        }
    }

    public function test_no_codes_means_neutral(): void {
        $factor = $this->factor();
        $this->assertSame(\tool_mfa\plugininfo\factor::STATE_NEUTRAL, $factor->get_state());
    }

    public function test_only_hashes_are_stored(): void {
        global $DB, $USER;
        $factor = $this->factor();
        $codes = $this->make_codes($factor);
        $record = $DB->get_record('tool_mfa', ['userid' => $USER->id, 'factor' => 'backupcodes'], '*', MUST_EXIST);
        foreach ($codes as $code) {
            $this->assertStringNotContainsString($code, $record->secret);
        }
        $this->assertSame(\tool_mfa\plugininfo\factor::STATE_UNKNOWN, $factor->get_state());
    }

    public function test_a_code_works_once_and_typing_is_forgiving(): void {
        $factor = $this->factor();
        $codes = $this->make_codes($factor);
        $typed = strtoupper(str_replace('-', ' ', $codes[3]));
        $this->assertSame([], $factor->login_form_validation(['backupcode' => " $typed "]));
        $this->assertArrayHasKey('backupcode', $factor->login_form_validation(['backupcode' => $codes[3]]));
        $this->assertSame([], $factor->login_form_validation(['backupcode' => $codes[0]]));
        $this->assertSame(8, $factor->remaining_codes());
        global $USER;
        $active = $factor->get_active_user_factors($USER);
        $record = reset($active);
        $this->assertSame(get_string('labelcount', 'factor_backupcodes', 8), $record->label);
    }

    public function test_a_wrong_code_is_refused(): void {
        $factor = $this->factor();
        $this->make_codes($factor);
        $this->assertArrayHasKey('backupcode', $factor->login_form_validation(['backupcode' => 'aaaaa-bbbbb']));
        $this->assertArrayHasKey('backupcode', $factor->login_form_validation(['backupcode' => '']));
    }

    public function test_new_codes_replace_the_old_ones(): void {
        global $DB, $USER;
        $factor = $this->factor();
        $old = $this->make_codes($factor);
        $active = $factor->get_active_user_factors($USER);
        $new = factor::generate_codes();
        $factor->replace_user_factor((object)['codes' => implode(' ', $new), 'saved' => 1], reset($active)->id);

        $this->assertCount(1, $factor->get_active_user_factors($USER));
        $this->assertArrayHasKey('backupcode', $factor->login_form_validation(['backupcode' => $old[0]]));
        $this->assertSame([], $factor->login_form_validation(['backupcode' => $new[0]]));
        $this->assertSame(1, $DB->count_records('tool_mfa', ['userid' => $USER->id, 'factor' => 'backupcodes', 'revoked' => 1]));
    }

    public function test_setup_form_needs_the_codes_saved_and_well_formed(): void {
        $factor = $this->factor();
        $codes = implode(' ', factor::generate_codes());
        $this->assertSame([], $factor->setup_factor_form_validation(['codes' => $codes, 'saved' => 1]));
        $this->assertArrayHasKey('saved', $factor->setup_factor_form_validation(['codes' => $codes]));
        $this->assertArrayHasKey('saved', $factor->setup_factor_form_validation(['codes' => 'aaaaa-aaaaa', 'saved' => 1]));
    }

    public function test_the_last_code_warns_to_make_new_ones(): void {
        $factor = $this->factor();
        $codes = $this->make_codes($factor);
        foreach (array_slice($codes, 0, 9) as $code) {
            $this->assertSame([], $factor->login_form_validation(['backupcode' => $code]));
        }
        \core\notification::fetch();
        $this->assertSame([], $factor->login_form_validation(['backupcode' => $codes[9]]));
        $this->assertSame(0, $factor->remaining_codes());
        $messages = array_map(fn($n) => $n->get_message(), \core\notification::fetch());
        $this->assertStringContainsString(get_string('nocodesleft', 'factor_backupcodes'), implode(' ', $messages));
    }
}
