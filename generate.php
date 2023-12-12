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
require_once($CFG->libdir.'/navigationlib.php');

use block_openai_questions\handler;

// We reuse this page both for the initial question text form as well as the edit questions page.
// During setup, we'll assume which is which based on whether the "id" parameter is set or not (id => generate questions, no id => edit questions)
// Once the page is set up, if no id parameter is set, we'll check to see if any form data was passed to determine if we actually ARE on the edit questions page or not.
// If not, we just redirect to the course.

// First, figure out the current course. If passed in the URL, use that; otherwise, grab the session value
// Also set the URL accordingly
$courseid = optional_param('id', 1, PARAM_INTEGER);
$url = null;
if ($courseid !== 1) {
  $_SESSION["openai_questions_course"] = $courseid;
  $url = new moodle_url($CFG->wwwroot . "/blocks/openai_questions/generate.php", ['id' => $courseid]);
} else {
  $url = new moodle_url($CFG->wwwroot . "/blocks/openai_questions/generate.php");
}

// Then get the course record from this value, check that the user has permission to do this, and add the relevant crumbs to the nav trail
$course = $DB->get_record('course', array('id' => $_SESSION["openai_questions_course"]), '*', MUST_EXIST);

require_login($course);
if (!has_capability('moodle/course:manageactivities', context_course::instance($course->id))) {
  throw new \moodle_exception("capability_error", "block_openai_questions", "", get_string('error_capability', 'block_openai_questions'));
}

$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', ['id' => $course->id]));
if ($courseid !== 1) {
  $PAGE->navbar->add(get_string("openai_questions", "block_openai_questions", $url));
} else {
  $PAGE->navbar->add(
    get_string("openai_questions", "block_openai_questions"), 
    new moodle_url('/blocks/openai_questions/generate.php', ['id' => $course->id])
  );
  $PAGE->navbar->add(get_string("editquestions", "block_openai_questions", $url));
}

// Set up page
$pagetitle = get_string('openai_questions', 'block_openai_questions');
$PAGE->set_course($course);
$PAGE->set_url($url);
$PAGE->set_title($pagetitle);
$PAGE->set_pagelayout('standard');

$mform = new generate_form();
$fromform = $mform->get_data();

if ($mform->is_cancelled()) {

  redirect($CFG->wwwroot . "/course/view.php?id=" . $course->id);
  
} else if ($fromform) {
  // The form was submitted, so render the edit questions page with the result

  $PAGE->requires->js('/blocks/openai_questions/lib.js');
  $PAGE->set_heading(get_string('editquestions', 'block_openai_questions'));
  echo $OUTPUT->header();

  $handler = new handler($fromform->sourcetext, $fromform->qtype);
  $questions = $handler->fetch_response($fromform->number_of_questions);

  $output = html_writer::tag('input', '', ['type' => 'hidden', 'value' => $fromform->courseid, 'id' => 'courseid']);
  $output .= html_writer::tag('input', '', ['type' => 'hidden', 'value' => $fromform->qtype, 'id' => 'qtype']);

  if (count($questions) != $fromform->number_of_questions) {
    $output .= html_writer::tag('p', get_string('numbermismatch', 'block_openai_questions'));
  }

  foreach ($questions as $question_data) {
    $output .= html_writer::start_div('block_openai_questions-question');

      $output .= html_writer::start_div('block_openai_questions-text-container');
        $output .= html_writer::tag('textarea', $question_data["question"], ['class' => 'block_openai_questions-title']);
        foreach ($question_data['answers'] as $letter => $answer) {
          $output .= html_writer::start_div('block_openai_questions-answer');
            if ($fromform->qtype == 'multichoice') {
              $output .= html_writer::tag('button', get_string("markascorrect", "block_openai_questions"), ['class' => 'block_openai_questions-markCorrectButton']);
            }

            if (array_key_exists('correct', $question_data) && $question_data['correct'] == $letter) {
              $output .= html_writer::tag('input', '', ['type' => 'text', 'value' => $answer, 'class' => 'block_openai_questions-correct', 'data-qid' => $letter]);
            } else {
              $output .= html_writer::tag('input', '', ['type' => 'text', 'value' => $answer, 'data-qid' => $letter]);
            }
          $output .= html_writer::end_div();
        }
      $output .= html_writer::end_div();

      $output .= html_writer::start_div('block_openai_questions-button-container');
        $output .= html_writer::tag('button', '<i class="fa fa-trash"></i>', ['class' => 'block_openai_questions-delete', 'title' => 'Remove question']);
      $output .= html_writer::end_div();

    $output .= html_writer::end_div();
  }

  $output .= html_writer::tag('input', '', ['type' => 'submit', 'value' => get_string('addtoqbank', 'block_openai_questions'), 'class' => 'btn btn-primary block_openai_questions-addToQBank', 'id' => 'addToQBank']);
  $output .= html_writer::tag('a', '<input type="submit" class="btn btn-secondary" value="' . get_string('cancel', 'block_openai_questions') . '"/>', ['href' => "/course/view.php?id=$fromform->courseid"]);

  echo $output;
  $PAGE->requires->js_init_call('init', [$_SESSION['openai_questions_course']]);

} else {

  // If the course id isn't set and data wasn't actually passed, redirect to course. Somebody went to this page directly, I guess
  if ($courseid === 1 && (!$fromform && !file_get_contents('php://input'))) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
  }

  $PAGE->set_heading($pagetitle);
  echo $OUTPUT->header();
  $mform->set_data(['courseid' => $courseid]);
  $mform->display();

}

echo $OUTPUT->footer();
