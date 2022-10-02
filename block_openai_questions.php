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
 * Main block definition.
 *
 * @package    block_openai_questions
 * @copyright  2022 Bryce Yoder (me@bryceyoder.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_openai_questions extends block_base {

    function init() {
        $this->title = get_string('openai_questions', 'block_openai_questions');
    }

    function get_content() {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $context = context_course::instance($this->page->course->id);
        if (!has_capability('moodle/course:manageactivities', $context)) {
            return;
        }

        $this->content         =  new stdClass;
        $this->content->text   = 'Click <a href="/blocks/openai_questions/generate.php?id=' . $this->page->course->id . '">here</a> to generate questions';

        return $this->content;
    }

    function has_config() {return true;}
}