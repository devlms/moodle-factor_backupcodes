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
 * Strings for backup codes.
 *
 * @package    factor_backupcodes
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['action:manage'] = 'Manage backup codes';
$string['action:revoke'] = 'Remove backup codes';
$string['badset'] = 'These codes could not be saved. Reload the page to get a new set.';
$string['download'] = 'Download as a text file';
$string['downloadheader'] = 'Backup codes for {$a}. Each code works once.';
$string['entercode'] = 'Backup code';
$string['info'] = 'One-time codes for when you cannot use your usual method.';
$string['labelcount'] = 'Backup codes ({$a} left)';
$string['logindesc'] = 'Enter one of your backup codes. Each code works once.';
$string['loginoption'] = 'Use a backup code';
$string['loginskip'] = 'I don\'t have my codes';
$string['loginsubmit'] = 'Continue';
$string['managefactor'] = 'Manage backup codes';
$string['managefactorbutton'] = 'Manage';
$string['manageinfo'] = 'You have {$a} backup codes left.';
$string['mustsave'] = 'Save your codes first, then tick this box.';
$string['nocodesleft'] = 'You have used your last backup code. Make a new set in your preferences under Multi-factor authentication.';
$string['pluginname'] = 'Backup codes';
$string['privacy:metadata'] = 'The backup codes factor keeps only one-way hashes of the codes, in the multi-factor authentication tables of Moodle.';
$string['replacefactor'] = 'Make new backup codes';
$string['replacefactorconfirmation'] = 'Make new backup codes? Your current codes will stop working.';
$string['revokefactorconfirmation'] = 'Remove your backup codes? They will stop working.';
$string['saved'] = 'I have saved these codes somewhere safe';
$string['settings:description'] = 'Users can make ten one-time codes to sign in when they do not have their authenticator app or security key. Only one-way hashes of the codes are stored.';
$string['settings:shortdescription'] = 'Let users sign in with a one-time backup code.';
$string['setupfactor'] = 'Set up backup codes';
$string['setupfactorbutton'] = 'Set up';
$string['setupintro'] = 'Keep these codes somewhere safe, away from your phone: in a password manager, or printed. Each code works once. They are shown only now.';
$string['summarycondition'] = 'has backup codes';
$string['wrongcode'] = 'That code is not valid, or it was already used.';
