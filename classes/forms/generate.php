<?php
// This file is part of Moodle - http://moodle.org/
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
 * Question generation form
 *
 * @package    block_openai_questions
 * @copyright  2022 Bryce Yoder (me@bryceyoder.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class generate_form extends moodleform {
    public function definition() {
        global $CFG;
       
        $mform = $this->_form;

        $mform->addElement('textarea', 'sourcetext', get_string('sourcetext', 'block_openai_questions'),'wrap="virtual" rows="20" cols="50"');
        $mform->setType('sourcetext', PARAM_TEXT);

        $qtypes = ['multichoice' => 'Multiple choice', 'truefalse' => 'True/False', 'shortanswer' => 'Short answer'];
        $mform->addElement('select', 'qtype', get_string('qtype', 'block_openai_questions'), $qtypes);

        $mform->addElement('text', 'number_of_questions', get_string('numquestions', 'block_openai_questions'));
        $mform->setType('number_of_questions', PARAM_INTEGER);

        $mform->addElement('hidden', 'courseid', '1');
        $mform->setType('courseid', PARAM_INTEGER);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = [];
        if ($data['number_of_questions'] < 1 || $data['number_of_questions'] > 20) {
            $errors['number_of_questions'] = get_string('notanumber', 'block_openai_questions');
        }

        if (strlen($data['sourcetext']) < 100 || strlen($data['sourcetext']) > 10000) {
            $errors['sourcetext'] = get_string('sourcetextcharlength', 'block_openai_questions');
        }

        return $errors;
    }
}