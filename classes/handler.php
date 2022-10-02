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

namespace block_openai_questions;

/**
 * Class for handling question generation
 *
 * @package     block_openai_questions
 * @copyright   2022 Bryce Yoder (me@bryceyoder.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

class handler {

    private $sourcetext;
    private $qtype;
    private $apikey;

    private $last_response = '';
    private $questions;

    function __construct($sourcetext, $qtype) {
        $this->sourcetext = $sourcetext;
        $this->qtype = $qtype;
        $this->apikey = get_config('block_openai_questions', 'apikey');
    }

    /**
     * Fetch a GPT-3 generation from a prompt
     * 
     * @param int prompt (optional): The prompt to pass to OpenAI
     * @return Array: An array of questions parsed from the GPT-3 generation
     */
    public function fetch_response($prompt = null) {
        $curlbody = [
            "prompt" => $prompt ? $prompt : $this->get_qtype_prompt(),
            "temperature" => 1,
            "max_tokens" => 1000,
            "top_p" => 1,
            "frequency_penalty" => 0.25,
            "presence_penalty" => 0,
            "stop" => ['Answer 16:']
        ];
        
        $curl = new \curl();
        $curl->setopt(array(
            'CURLOPT_HTTPHEADER' => array(
                'Authorization: Bearer ' . $this->apikey,
                'Content-Type: application/json'
            ),
        ));

        $response = $curl->post('https://api.openai.com/v1/engines/text-davinci-001/completions', json_encode($curlbody));
        $this->last_response .= "\n" . json_decode($response)->choices[0]->text;
        return $this->parse_response($response);
    }

    /**
     * The first time questions are generated, an example paragraph and questions are passed so GPT-3 knows the format to use.
     * However, only a few questions can be generated this way. So we can call this function to request more questions from GPT-3,
     * this time using the user-submitted paragraph and the three originally-generated questions as the example
     * 
     * @param int number_of_questions: The number of questions to tell GPT-3 to generate (won't necessarily work though)
     * @return Array: An array of questions parsed from the GPT-3 generation
     */
    public function get_next_question_set($number_of_questions) {
        $prompt = "The following information is followed by $number_of_questions $this->qtype questions:\n\n";
        $prompt .= $this->sourcetext . "\n\n";
        $prompt .= $this->last_response . "\n";
        return $this->fetch_response($prompt);
    }

    /**
     * The first response to GPT-3 starts with an example so the AI knows what the questions should look like.
     * This function gets the right example based on the passed question type.
     * @return string: The entire example prompt to pass to GPT-3
     */
    private function get_qtype_prompt() {
        $prompt = "The following is a paragraph of information, followed by three $this->qtype questions:\n\nOn 19 March 1882, construction of the Sagrada Família began under architect Francisco de Paula del Villar. In 1883, when Villar resigned, Gaudí took over as chief architect, transforming the project with his architectural and engineering style, combining Gothic and curvilinear Art Nouveau forms. Gaudí devoted the remainder of his life to the project, and he is buried in the crypt. At the time of his death in 1926, less than a quarter of the project was complete.\n\n";
        $qtype_prompts = [
            'shortanswer' => "Question 1: On what date did construction start?\nAnswer: 19 March 1882\n\nQuestion 2: Who was the original architect of the basilica?\nAnswer: Francisco de Paula del Villar\n\nQuestion 3: How much of the project was completed when Gaudi died?\nAnswer: Less than a quarter\n\n-----\n\nThe following is another excerpt, again followed by three short answer questions:\n\n",
            'truefalse' => "Question 1: Construction started on 19 March 1882\nAnswer: True\n\nQuestion 2: The original architect was Antoni Gaudi.\nAnswer: False\n\nQuestion 3: Over half of the basilica was finished when Gaudi died.\nAnswer: False\n\n-----\n\nThe following is another excerpt, again followed by three true/false questions:\n\n",
            'multichoice' => "Question 1: On what date did construction start?\nAnswer A (correct): 1882\nAnswer B: 1893\nAnswer C: 1926\nAnswer D: 1918\n\nQuestion 2: Who was the original architect of the basilica?\nAnswer A: Antoni Gaudi\nAnswer B: Francsico de Goya\nAnswer C (correct): Francisco de Paula del Villar\nAnswer D: Louis Sullivan\n\nQuestion 3: How much of the basilica was finished when Gaudi died?\nAnswer A: Over a third\nAnswer B: Nearly all of it\nAnswer C: Around half\nAnswer D (correct): Less than a quarter\n\n-----\n\nThe following is another excerpt, again followed by three multiple choice questions:\n\n"
        ];

        return $prompt . $qtype_prompts[$this->qtype] . $this->sourcetext . "\n\n";
    }

    /**
     * Given a response from GPT-3, try to parse it into a structured array of questions and answers
     * @param JSON generation: The JSON response from GPT-3
     * @return Array: The structured array of questions
     */
    private function parse_response($generation) {
        $questions_obj = json_decode($generation);
        $split_questions = explode('Question', $questions_obj->choices[0]->text);
        unset($split_questions[0]);

        $questions = $this->questions ? $this->questions : [];
        $letter_array = ['A', 'B', 'C', 'D'];

        foreach ($split_questions as $question) {
            $split_answers = explode('Answer', $question);
            $question_text = str_replace('\n', '', trim(explode(':', $split_answers[0])[1]));
            $questions[$question_text] = ['answers' => []];
            unset($split_answers[0]);

            foreach ($split_answers as $index => $answer) {
                if (strpos($answer, 'correct') !== false) {
                    $questions[$question_text]['correct'] = $letter_array[$index-1];
                }

                $questions[$question_text]['answers'][$letter_array[$index-1]] = 
                    explode(':', str_replace('\n', '', $answer))[1];
            }
        }

        $this->questions = $questions;
        return($questions);
    }

}
