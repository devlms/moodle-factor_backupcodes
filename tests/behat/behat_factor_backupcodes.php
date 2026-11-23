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

/**
 * Behat steps for backup codes.
 *
 * @package    factor_backupcodes
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
require_once(__DIR__ . '/../../../../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * Steps that read a code from the setup page and type it at login.
 */
class behat_factor_backupcodes extends behat_base {
    /** @var string the code remembered from the setup page */
    private static string $code = '';

    /**
     * Remember one of the codes on the setup page.
     *
     * @Given /^I remember backup code number "(?P<number>\d+)"$/
     * @param int $number 1 to 10
     */
    public function i_remember_backup_code_number(int $number): void {
        $codes = $this->getSession()->getPage()->findAll('css', 'ol.factor_backupcodes_codes code');
        if (!isset($codes[$number - 1])) {
            throw new ExpectationException("No backup code number $number on the page", $this->getSession());
        }
        self::$code = trim($codes[$number - 1]->getText());
    }

    /**
     * Type the remembered code.
     *
     * @Given /^I enter the remembered backup code$/
     */
    public function i_enter_the_remembered_backup_code(): void {
        $this->execute('behat_forms::i_set_the_field_to', [get_string('entercode', 'factor_backupcodes'), self::$code]);
    }
}
