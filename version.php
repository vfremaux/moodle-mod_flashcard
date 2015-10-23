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
 * @package mod-flashcard
 * @copyright Valery Fremaux
 * @copyright 2011 onwards Tomasz Muras
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$plugin->version  = 2015061200;  // The current module version (Date: YYYYMMDDXX)
$plugin->requires = 2015050500;  // Requires this Moodle version (2.3 and above)
$plugin->cron     = 1; // Period for cron to check this module (secs)
$plugin->component = 'mod_flashcard'; // Full name of the plugin (used for diagnostics)
$plugin->release = '2.9.0 (Build 2014071100)';
$plugin->maturity = MATURITY_RC;
