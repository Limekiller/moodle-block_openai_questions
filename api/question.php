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
 * Question api endpoint
 *
 * @package    block_openai_questions
 * @copyright  2022 Bryce Yoder (me@bryceyoder.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die();
}

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/questionlib.php');
global $DB;

$response = json_decode(file_get_contents('php://input'));

require_login();
$course_context = context_course::instance($response->courseid);
if (!has_capability('moodle/course:manageactivities', $course_context)) {
    http_response_code(401);
    die();
}

// Figure out the ID of the "top" category for this course
$sql = "SELECT id from {question_categories} WHERE contextid = ?";
$top_category_id_for_course = $DB->get_records_sql($sql, [$course_context->id]);
$top_category_id_for_course = reset($top_category_id_for_course)->id;

// Then get the first listed category in the database with the "top" as the parent
$sql = "SELECT id from {question_categories} WHERE parent = ?";
$category_id = $DB->get_records_sql($sql, [$top_category_id_for_course]);
$category_id = reset($category_id)->id;

foreach ($response->questions as $question => $question_data) {
    $answer_array = $question_data->answers;

    $question_obj = new stdClass();
    $question_obj->category  = $category_id;
    $question_obj->qtype     = $response->qtype;
    $question_obj->createdby = $USER->id;

    $form = new stdClass();
    $form->category = $category_id;
    $form->name = $question;
    $form->questiontext = [
        'format' => '1',
        'text' => $question   
    ];
    $form->generalfeedback = [
        'format' => '1',
        'text' => ''
    ];
    $form->defaultmark = 1;
    $form->penalty = 0;
    $form->status = 'ready';

    switch ($response->qtype) {
        case 'truefalse':
            $form->correctanswer = $answer_array->A == 'True' ? 1 : 0;
        
            $form->feedbacktrue = array();
            $form->feedbacktrue['format'] = '1';
            $form->feedbacktrue['text'] = '';

            $form->feedbackfalse = array();
            $form->feedbackfalse['format'] = '1';
            $form->feedbackfalse['text'] = '';
            break;
        
        case 'shortanswer':
            $form->usecase = false;
            $form->answer = [$answer_array->A];
            $form->fraction = ['1.0'];
            $form->feedback = [['text' => '', 'format' => '1']];
            break;

        case 'multichoice':
            $form->noanswers = 4;
            $form->numhints = 0;
            $form->shuffleanswers = 1;
            $form->answernumbering = 'ABCD';
            $form->showstandardinstruction = 0;
            $form->single = '1';
            $form->answer = $form->feedback = $form->fraction = [];
            $form->shownumcorrect = 0;

            $form->correctfeedback = ['text' => '', 'format' => '1'];
            $form->partiallycorrectfeedback = ['text' => '', 'format' => '1'];
            $form->incorrectfeedback = ['text' => '', 'format' => '1'];

            foreach ($answer_array as $letter => $answer) {
                array_push($form->answer, ['text' => $answer, 'format' => '1']);
                array_push($form->feedback, ['text' => '', 'format' => '1']);
                array_push($form->fraction, $question_data->correct == $letter ? '1' : '0');
            }
            break;
    }

    \question_bank::get_qtype($response->qtype)->save_question($question_obj, $form);
}

http_response_code(200);
echo json_encode([
    'response' => 200,
    'message' => 'Questions created successfully!',
    'data' => [
        'courseid' => $response->courseid
    ]
]);