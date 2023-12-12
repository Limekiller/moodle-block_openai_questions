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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Adds admin settings for the plugin.
 *
 * @package     block_openai_questions
 * @copyright   2022 Bryce Yoder (me@bryceyoder.com)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_configtext(
    'block_openai_questions/apikey',
    get_string('apikey', 'block_openai_questions'),
    get_string('apikeylabel', 'block_openai_questions'),
    ''
));

$settings->add(new admin_setting_configselect(
    'block_openai_questions/model',
    get_string('model', 'block_openai_questions'),
    get_string('modellabel', 'block_openai_questions'),
    'gpt-3.5-turbo-1106',
    [
        'gpt-4' => 'gpt-4',
        'gpt-4-1106-preview' => 'gpt-4-1106-preview',
        'gpt-4-0613' => 'gpt-4-0613',
        'gpt-4-0314' => 'gpt-4-0314',
        'gpt-3.5-turbo' => 'gpt-3.5-turbo',
        'gpt-3.5-turbo-16k' => 'gpt-3.5-turbo-16k',
        'gpt-3.5-turbo-1106' => 'gpt-3.5-turbo-1106',
        'gpt-3.5-turbo-0613' => 'gpt-3.5-turbo-0613',
        'gpt-3.5-turbo-16k-0613' => 'gpt-3.5-turbo-16k-0613',
    ]
));