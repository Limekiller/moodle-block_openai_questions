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

$response = json_decode(file_get_contents('php://input'));
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/questionlib.php');
global $DB;

require_login();
// TODO: add capability check for teacher

// Figure out the ID of the "top" category for this course
$course_context = context_course::instance($response->courseid);
$sql = "SELECT id from {question_categories} WHERE contextid = ?";
$top_category_id_for_course = $DB->get_records_sql($sql, [$course_context->id]);
$top_category_id_for_course = reset($top_category_id_for_course)->id;

// Then get the first listed category in the database with this as the parent
$sql = "SELECT id from {question_categories} WHERE parent = ?";
$category_id = $DB->get_records_sql($sql, [$top_category_id_for_course]);
$category_id = reset($category_id)->id;

foreach ($response->questions as $question => $answer_array) {
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
    $form->penalty = 1;
    $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

    switch ($response->qtype) {
        case 'truefalse':
            $form->correctanswer = $answer_array->A == 'True' ? 1 : 0;
            $form->defaultmark = 1;

            $form->generalfeedback = array();
            $form->generalfeedback['format'] = '1';
            $form->generalfeedback['text'] = '';
        
            $form->feedbacktrue = array();
            $form->feedbacktrue['format'] = '1';
            $form->feedbacktrue['text'] = '';

            $form->feedbackfalse = array();
            $form->feedbackfalse['format'] = '1';
            $form->feedbackfalse['text'] = '';
            break;
        
        case 'shortanswer':
            $form->defaultmark = 1.0;
            $form->generalfeedback = [
                'format' => '1',
                'text' => ''
            ];
            $form->usecase = false;
            $form->answer = [$answer_array->A];
            $form->fraction = ['1.0'];
            $form->feedback = [''];
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