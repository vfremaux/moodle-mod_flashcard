<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details.
 *
 * @package     mod_flashcard
 * @category    mod
 * @author      Tomasz Muras
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright   2011 onwards Tomasz Muras
 * @copyright   2013 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2019121101;  // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2020060900;  // Requires this Moodle version (3.6 and above).
$plugin->component = 'mod_flashcard'; // Full name of the plugin (used for diagnostics).
$plugin->release = '3.9.0 (Build 2019121101)';
$plugin->maturity = MATURITY_STABLE;
$plugin->supported = [39, 311];

// Non Moodle attributes.
$plugin->codeincrement = '3.9.0005';