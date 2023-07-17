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
 * Lang strings
 *
 * @package    block_openai_questions
 * @copyright  2022 Bryce Yoder (me@bryceyoder.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'OpenAI Question Generator';
$string['openai_questions'] = 'Question Generator';
$string['openai_questions:addinstance'] = 'Add a new Question Generator block';
$string['privacy:metadata'] = 'The OpenAI Questions block, by default, neither stores personal user data nor sends it to OpenAI. However, text submitted by teachers in order to generate questions is sent in its entirety to OpenAI, and is then subject to OpenAI\'s privacy policy (https://openai.com/api/policies/privacy/), which may store messages in order to improve the API. Additionally, this text is then used to generate questions that may be saved to the site.';

$string['manage'] = 'OpenAI Question Generator Settings';
$string['apikey'] = 'OpenAI API Key';
$string['apikeylabel'] = 'The API key provided by OpenAI';
$string['model'] = 'Model';
$string['modellabel'] = 'The model to use in order to generate questions';

$string['sourcetext'] = 'Source text';
$string['qtype'] = 'Question type';
$string['numquestions'] = 'Number of questions to generate';
$string['notanumber'] = 'Value must be a number that is between 1 and 20';
$string['sourcetextcharlength'] = 'Number of characters must be between 100 and 64,000';

$string['editquestions'] = 'Edit Questions';