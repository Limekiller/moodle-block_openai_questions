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

    function __construct($sourcetext, $qtype) {
        $this->sourcetext = $sourcetext;
        $this->qtype = $qtype;
        $this->apikey = get_config('block_openai_questions', 'apikey');
    }

    /**
     * Fetch a GPT generation from a prompt
     * 
     * @param int number_of_questions (optional): The number of questions to try to generate
     * @return Array: An array of questions parsed from the GPT-3 generation
     */
    public function fetch_response($number_of_questions=3) {
        $messages = $this->build_messages();
        array_push($messages, ["role" => "user", "content" => '{"number_of_questions": ' . $number_of_questions . ',  "text": "' . $this->sourcetext . '"}']);

        $response = $this->make_api_request($messages);
        if (property_exists($response, 'error')) {
            throw new \moodle_exception("openai_error", "block_openai_questions", "", $response->error->message);
        }

        $completion = json_decode($response->choices[0]->message->content, true);
        if (!$completion) {
            $completion = $this->attempt_json_conversion($response->choices[0]->message->content);
        }

        return $completion;
    }

    /**
     * The first response to GPT-3 starts with an example so the AI knows what the questions should look like.
     * This function gets the right example based on the passed question type.
     * @return string: The entire example prompt to pass to GPT-3
     */
    private function build_messages() {
        $qtype_prompts = [
            'shortanswer' => '[{"question": "On what date did construction start?", "answers": {"A": "19 March 1882"}}, {"question": "Who was the original architect of the basilica?", "answers": {"A": "Francisco de Paula del Villar"}}, {"question": "How much of the project was completed when Gaudi died?", "answers": {"A": "Less than a quarter"}}]',
            'truefalse' => '[{"question": "Construction started on 19 March 1882", "answers": {"A": "True"}}, {"question": "The original architect was Antoni Gaudi", "answers": {"A": "False"}}, {"question": "Over half of the basilica was finished when Gaudi died.", "answers": {"A": "False"}}]',
            'multichoice' => '[{"question": "On what date did construction start?", "answers": {"A": "1882", "B": "1893", "C": "1926", "D": "1918"}, "correct": "A"}, {"question": "Who was the original architect of the basilica?", "answers": {"A": "Antoni Gaudi", "B": "Francsico de Goya", "C": "Francisco de Paula del Villar", "D": "Louis Sullivan"}, "correct": "C"}, {"question": "How much of the basilica was finished when Gaudi died?", "answers": {"A": "Over a third", "B": "Nearly all of it", "C": "Around half", "D": "Less than a quarter"}, "correct": "D"}]'
        ];

        $messages = [
            ["role" => "system", "content" => "Generate $this->qtype questions from text in JSON format. Do not return normal text, just JSON. For example, the following is an example of the input JSON:"],
            [
                "role" => "system", 
                "content" => '{"number_of_questions": 3, "text": "On 19 March 1882, construction of the Sagrada Família began under architect Francisco de Paula del Villar. In 1883, when Villar resigned, Gaudí took over as chief architect, transforming the project with his architectural and engineering style, combining Gothic and curvilinear Art Nouveau forms. Gaudí devoted the remainder of his life to the project, and he is buried in the crypt. At the time of his death in 1926, less than a quarter of the project was complete."}',
            ],
            ["role" => "system", "content" => "Here is an example of the output JSON containing the $this->qtype questions. The response MUST follow this structure:"],
            ["role" => "system", "content" => $qtype_prompts[$this->qtype]]
        ];

        if ($this->qtype === "truefalse") {
            array_push($messages, ["role" => "system", "content" => "The answers given MUST either be the string 'True' or the string 'False'."]);
        }

        return $messages;
    }

    /**
     * If GPT fails to provide parseable JSON, we run it through one more prompt to try to massage the data into something we can use
     * @param string responsetext: The text to convert into JSON
     * @return string: The JSON string
     */
    private function attempt_json_conversion($responsetext) {
        $messages = [
            ["role" => "system", "content" => "Please convert any given input into valid JSON. Do not return anything else except properly formatted JSON based on the input."],
            ["role" => "user", "content" => $responsetext]
        ];

        $response = $this->make_api_request($messages);

        $completion = json_decode($response->choices[0]->message->content, true);
        if (!$completion) {
            throw new \moodle_exception("gpt_format_error", "block_openai_questions", "", "GPT failed to return questions in the correct format. Sorry, there's nothing you can do about this except try generating the questions again. You can refresh this page to re-attempt question generation.\n\nHere's the response received from GPT:\n\"" . $response->choices[0]->message->content . '"');
        }

        return $completion;
    }

    /**
     * Helper method for making API requests
     * @param Array messages: The list of messages to send to OpenAI
     * @return Array: The parsed JSON response
     */
    private function make_api_request($messages) {
        $curlbody = [
            "model" => "gpt-3.5-turbo-0301",
            "messages" => $messages,
            "temperature" => 1,
            "top_p" => 1,
            "frequency_penalty" => 0.25,
            "presence_penalty" => 0,
        ];
        
        $curl = new \curl();
        $curl->setopt(array(
            'CURLOPT_HTTPHEADER' => array(
                'Authorization: Bearer ' . $this->apikey,
                'Content-Type: application/json'
            ),
        ));

        $response = $curl->post('https://api.openai.com/v1/chat/completions', json_encode($curlbody));
        $response = json_decode($response);
        return $response;
    }

}
