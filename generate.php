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
 * Generation form page
 *
 * @package    block_openai_questions
 * @copyright  2022 Bryce Yoder (me@bryceyoder.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/forms/generate.php');
use block_openai_questions\handler;
use block_openai_questions\output\question_page;

$pagetitle = get_string('openai_questions', 'block_openai_questions');
$courseid = optional_param('id', 1, PARAM_INTEGER);

require_login();
if (!has_capability('block/openai_questions:addinstance', context_course::instance($courseid))) {
  throw new moodle_exception('Sorry dawg');
}

$PAGE->set_context(context_system::instance());
// $PAGE->set_context(context_course::instance($courseid));

$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);
$PAGE->set_url($CFG->wwwroot . '/blocks/openai_questions/generate.php');

$mform = new generate_form();

if ($mform->is_cancelled()) {
  var_dump(':/');
  die();
} else if ($fromform = $mform->get_data()) {
  $PAGE->requires->js('/blocks/openai_questions/lib.js');
  $PAGE->set_heading(get_string('editquestions', 'block_openai_questions'));
  echo $OUTPUT->header();

  $handler = new handler($fromform->sourcetext, $fromform->qtype);
  $questions = [];
  $questions = $handler->fetch_response(); // Initial prompt with example question and answers generates three questions
  $questions = $handler->get_next_question_set($fromform->number_of_questions); // Now feed the user-submitted text and generated questions back in to try to get more

  $output = html_writer::tag('input', '', ['type' => 'hidden', 'value' => $fromform->courseid, 'id' => 'courseid']);
  $output .= html_writer::tag('input', '', ['type' => 'hidden', 'value' => $fromform->qtype, 'id' => 'qtype']);

  foreach ($questions as $question => $answer_array) {
    $output .= html_writer::start_div('block_openai_questions-question');
    $output .= html_writer::start_div('text-container');
    $output .= html_writer::tag('textarea', $question, ['class' => 'title']);
    foreach ($answer_array['answers'] as $letter => $answer) {
      $output .= html_writer::start_div('answer');

      if ($fromform->qtype == 'multichoice') {
        $output .= html_writer::tag('button', 'Mark as correct', ['class' => 'markCorrectButton']);
      }

      if (array_key_exists('correct', $answer_array) && $answer_array['correct'] == $letter) {
        $output .= html_writer::tag('input', '', ['type' => 'text', 'value' => $answer, 'class' => 'correct', 'data-qid' => $letter]);
      } else {
        $output .= html_writer::tag('input', '', ['type' => 'text', 'value' => $answer, 'data-qid' => $letter]);
      }
      
      $output .= html_writer::end_div();
    }
    $output .= html_writer::end_div();
    $output .= html_writer::start_div('button-container');
    $output .= html_writer::tag('button', '<i class="fa fa-trash"></i>', ['class' => 'delete']);
    $output .= html_writer::end_div();
    $output .= html_writer::end_div();
  }

  $output .= html_writer::tag('input', '', ['type' => 'submit', 'value' => 'Add to question bank', 'class' => 'btn btn-primary', 'id' => 'addToQBank']);
  $output .= html_writer::tag('input', '', ['type' => 'submit', 'value' => 'Regenerate questions', 'class' => 'btn btn-secondary']);
  $output .= html_writer::tag('a', '<input type="submit" class="btn btn-secondary" value="Cancel"/>', ['href' => "/course/view.php?id=$fromform->courseid"]);

  echo $output;
  $PAGE->requires->js_init_call('init');
} else {
  $PAGE->set_heading($pagetitle);
  echo $OUTPUT->header();
  $mform->set_data(['courseid' => $courseid]);
  $mform->display();
}

echo $OUTPUT->footer();
