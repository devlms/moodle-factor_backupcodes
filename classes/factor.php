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

use MoodleQuickForm;
use stdClass;
use tool_mfa\local\factor\object_factor_base;
use tool_mfa\plugininfo\factor as factorinfo;

/**
 * Backup codes: ten one-time codes for when the usual factor (an authenticator app, a security key) is not at hand.
 *
 * Only password hashes of the codes are stored, all in one tool_mfa record per user. A code is removed once used.
 *
 * @package    factor_backupcodes
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class factor extends object_factor_base {
    /** @var string characters in a code: no i, l, o, 0 or 1, so codes are easy to read and type */
    public const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    /** @var int codes in a set */
    public const COUNT = 10;

    /** @var string pattern of a code as shown */
    public const PATTERN = '/^[a-hj-km-np-z2-9]{5}-[a-hj-km-np-z2-9]{5}$/';

    /** @var string icon */
    protected $icon = 'fa-key';

    /**
     * A new set of codes, like k7m3q-x9wdp.
     *
     * @return string[]
     */
    public static function generate_codes(): array {
        $codes = [];
        while (count($codes) < self::COUNT) {
            $code = '';
            for ($i = 0; $i < 10; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)] . ($i === 4 ? '-' : '');
            }
            $codes[$code] = $code;
        }
        return array_values($codes);
    }

    /**
     * A typed code in the stored form: lower case, letters and digits only.
     *
     * @param string $typed
     * @return string
     */
    private static function normalise(string $typed): string {
        return preg_replace('/[^a-z0-9]/', '', \core_text::strtolower($typed));
    }

    /**
     * The codes from the setup form, if they are a valid set.
     *
     * @param string $codes space separated
     * @return string[]|null
     */
    private static function parse_codes(string $codes): ?array {
        $list = preg_split('/\s+/', trim($codes), -1, PREG_SPLIT_NO_EMPTY);
        if (count($list) !== self::COUNT || count(array_unique($list)) !== self::COUNT) {
            return null;
        }
        foreach ($list as $code) {
            if (!preg_match(self::PATTERN, $code)) {
                return null;
            }
        }
        return $list;
    }

    /**
     * Neutral until the user has codes.
     *
     * @return string
     */
    public function get_state(): string {
        global $USER;
        if (!$this->get_active_user_factors($USER)) {
            return factorinfo::STATE_NEUTRAL;
        }
        return parent::get_state();
    }

    /**
     * The setup form carries the new codes so the ones shown are the ones saved.
     *
     * @param MoodleQuickForm $mform
     * @return MoodleQuickForm
     */
    public function setup_factor_form_definition(MoodleQuickForm $mform): MoodleQuickForm {
        $mform->addElement('hidden', 'codes', implode(' ', self::generate_codes()));
        $mform->setType('codes', PARAM_TEXT);
        return $mform;
    }

    /**
     * Show the codes, a download, and ask the user to confirm they saved them.
     *
     * @param MoodleQuickForm $mform
     * @return MoodleQuickForm
     */
    public function setup_factor_form_definition_after_data(MoodleQuickForm $mform): MoodleQuickForm {
        global $OUTPUT, $SITE;
        $codes = self::parse_codes((string)$mform->getElement('codes')->getValue()) ?? [];
        $heading = $mform->elementExists('replaceid') ? 'replacefactor' : 'setupfactor';
        $mform->addElement('html', $OUTPUT->heading(get_string($heading, 'factor_backupcodes'), 2));
        $mform->addElement('html', \html_writer::tag('p', get_string('setupintro', 'factor_backupcodes')));

        $items = '';
        foreach ($codes as $code) {
            $items .= \html_writer::tag('li', \html_writer::tag('code', $code, ['class' => 'fs-5']));
        }
        $mform->addElement('html', \html_writer::tag('ol', $items, ['class' => 'factor_backupcodes_codes']));

        $sitename = format_string($SITE->fullname, true, ['context' => \context_system::instance()]);
        $text = get_string('downloadheader', 'factor_backupcodes', $sitename) . "\n\n" . implode("\n", $codes) . "\n";
        $mform->addElement('html', \html_writer::tag('p', \html_writer::link(
            'data:text/plain;charset=utf-8;base64,' . base64_encode($text),
            get_string('download', 'factor_backupcodes'),
            ['download' => 'backup-codes.txt', 'class' => 'btn btn-secondary']
        )));

        $mform->addElement('checkbox', 'saved', '', get_string('saved', 'factor_backupcodes'));
        $mform->addRule('saved', get_string('required'), 'required', null, 'client');
        return $mform;
    }

    /**
     * The codes must be a valid set and the user must say they saved them.
     *
     * @param array $data
     * @return array
     */
    public function setup_factor_form_validation(array $data): array {
        if (empty($data['saved'])) {
            return ['saved' => get_string('mustsave', 'factor_backupcodes')];
        }
        if (self::parse_codes((string)($data['codes'] ?? '')) === null) {
            return ['saved' => get_string('badset', 'factor_backupcodes')];
        }
        return [];
    }

    /**
     * Save the hashes of a new set, replacing any older set.
     *
     * @param stdClass $data
     * @return stdClass|null
     */
    public function setup_user_factor(stdClass $data): stdClass|null {
        global $DB, $USER;
        $codes = self::parse_codes((string)($data->codes ?? ''));
        if ($codes === null) {
            return null;
        }
        foreach ($this->get_active_user_factors($USER) as $old) {
            $this->revoke_user_factor($old->id);
        }
        $now = time();
        $id = $DB->insert_record('tool_mfa', (object)[
            'userid' => $USER->id,
            'factor' => $this->name,
            'secret' => json_encode(array_map(fn($code) => password_hash(self::normalise($code), PASSWORD_DEFAULT), $codes)),
            'label' => get_string('labelcount', 'factor_backupcodes', count($codes)),
            'timecreated' => $now,
            'createdfromip' => $USER->lastip ?? '',
            'timemodified' => $now,
            'lastverified' => 0,
            'revoked' => 0,
        ]);
        $this->create_event_after_factor_setup($USER);
        return $DB->get_record('tool_mfa', ['id' => $id]);
    }

    /**
     * Generate a new set; the old set stops working.
     *
     * @param stdClass $data
     * @param int $id
     * @return stdClass|null
     */
    public function replace_user_factor(stdClass $data, int $id): stdClass|null {
        return $this->setup_user_factor($data);
    }

    /**
     * The code box on the login page.
     *
     * @param MoodleQuickForm $mform
     * @return MoodleQuickForm
     */
    public function login_form_definition(MoodleQuickForm $mform): MoodleQuickForm {
        $mform->disable_form_change_checker();
        $mform->addElement(
            'text',
            'backupcode',
            get_string('entercode', 'factor_backupcodes'),
            ['autocomplete' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false', 'size' => 15]
        );
        $mform->setType('backupcode', PARAM_RAW_TRIMMED);
        return $mform;
    }

    /**
     * Check a code and use it up.
     *
     * @param array $data
     * @return array errors
     */
    public function login_form_validation(array $data): array {
        global $DB, $USER;
        $typed = self::normalise((string)($data['backupcode'] ?? ''));
        $error = ['backupcode' => get_string('wrongcode', 'factor_backupcodes')];
        if (strlen($typed) !== 10) {
            return $error;
        }
        foreach ($this->get_active_user_factors($USER) as $record) {
            $hashes = json_decode((string)$record->secret, true) ?: [];
            foreach ($hashes as $index => $hash) {
                if (password_verify($typed, $hash)) {
                    unset($hashes[$index]);
                    $DB->update_record('tool_mfa', (object)['id' => $record->id,
                        'secret' => json_encode(array_values($hashes)), 'timemodified' => time(),
                        'label' => get_string('labelcount', 'factor_backupcodes', count($hashes))]);
                    $this->update_lastverified($record->id);
                    if (!$hashes) {
                        \core\notification::warning(get_string('nocodesleft', 'factor_backupcodes'));
                    }
                    return [];
                }
            }
        }
        return $error;
    }

    /**
     * Codes the current user has left.
     *
     * @return int
     */
    public function remaining_codes(): int {
        global $USER;
        $left = 0;
        foreach ($this->get_active_user_factors($USER) as $record) {
            $left += count(json_decode((string)$record->secret, true) ?: []);
        }
        return $left;
    }

    /**
     * Shown on the factor's card on the MFA preferences page.
     *
     * @param int $factorid
     * @return string
     */
    public function get_manage_info(int $factorid): string {
        return get_string('manageinfo', 'factor_backupcodes', $this->remaining_codes());
    }

    /**
     * All sets of this user.
     *
     * @param stdClass $user
     * @return array
     */
    public function get_all_user_factors($user): array {
        global $DB;
        return $DB->get_records('tool_mfa', ['userid' => $user->id, 'factor' => $this->name]);
    }

    /**
     * Users can remove their codes.
     *
     * @return bool
     */
    public function has_revoke(): bool {
        return true;
    }

    /**
     * Users can make a new set.
     *
     * @return bool
     */
    public function has_replace(): bool {
        return true;
    }

    /**
     * Users set it up themselves.
     *
     * @return bool
     */
    public function has_setup(): bool {
        return true;
    }

    /**
     * Show the set up button.
     *
     * @return bool
     */
    public function show_setup_buttons(): bool {
        return true;
    }

    /**
     * Nothing to do after passing.
     */
    public function post_pass_state(): void {
    }

    /**
     * States this factor can reach.
     *
     * @param stdClass $user
     * @return array
     */
    public function possible_states(stdClass $user): array {
        return [factorinfo::STATE_PASS, factorinfo::STATE_NEUTRAL, factorinfo::STATE_UNKNOWN];
    }

    /**
     * Set up button text.
     *
     * @return string
     */
    public function get_setup_string(): string {
        return get_string('setupfactorbutton', 'factor_backupcodes');
    }

    /**
     * Manage button text.
     *
     * @return string
     */
    public function get_manage_string(): string {
        return get_string('managefactorbutton', 'factor_backupcodes');
    }
}
