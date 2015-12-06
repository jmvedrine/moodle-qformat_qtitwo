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
 * IMSQTI 2.0 format question exporter.
 *
 * @package    qformat_qtitwo
 * @copyright  2005 Brian King brian@mediagonal.ch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('CLOZE_TRAILING_TEXT_ID', 9999999);

class qformat_qtitwo extends qformat_default {
    public $lang;

    public function provide_export() {
        return true;
    }

    public function indent_xhtml($source, $indenter = ' ') {
        // Xml tidier-upper
        // (c) Ari Koivula http://ventionline.com.

        // Remove all pre-existing formatting.
        // Remove all newlines.
        $source = str_replace("\n", '', $source);
        $source = str_replace("\r", '', $source);
        // Remove all tabs.
        $source = str_replace("\t", '', $source);
        // Remove all space after ">" and before "<".
        $source = preg_replace("/>( )*", ">/", $source);
        $source = preg_replace("/( )*<", "</", $source);

        // Iterate through the source.
        $level = 0;
        $sourcelen = strlen($source);
        $pt = 0;
        while ($pt < $sourcelen) {
            if ($source{$pt} === '<') {
                // We have entered a tag.
                // Remember the point where the tag starts.
                $startedat = $pt;
                $taglevel = 1;
                // If the second letter of the tag is "/", assume its an ending tag.
                if ($source{$pt + 1} === '/') {
                    $taglevel = -1;
                }
                // If the second letter of the tag is "!", assume its an "invisible" tag.
                if ($source{$pt + 1} === '!') {
                    $taglevel = 0;
                }
                // Iterate throught the source until the end of tag.
                while ($source{$pt} !== '>') {
                    $pt++;
                }
                // If the second last letter is "/", assume its a self ending tag.
                if ($source{$pt - 1} === '/') {
                    $taglevel = 0;
                }
                $taglenght = $pt + 1 - $startedat;

                // Decide the level of indention for this tag.
                // If this was an ending tag, decrease indent level for this tag..
                if ($taglevel === -1) {
                    $level--;
                }
                // Place the tag in an array with proper indention.
                $array[] = str_repeat($indenter, $level).substr($source, $startedat, $taglenght);
                // If this was a starting tag, increase the indent level after this tag.
                if ($taglevel === 1) {
                    $level++;
                }
                // If it was a self closing tag, dont do shit.
            }
            // Were out of the tag.
            // If next letter exists...
            if (($pt + 1) < $sourcelen) {
                // ... and its not an "<".
                if ($source{$pt + 1} !== '<') {
                    $startedat = $pt + 1;
                    // Iterate through the source until the start of new tag or until we reach the end of file.
                    while ($source{$pt} !== '<' && $pt < $sourcelen) {
                        $pt++;
                    }
                    // If we found a "<" (we didnt find the end of file).
                    if ($source{$pt} === '<') {
                        $taglenght = $pt - $startedat;
                        // Place the stuff in an array with proper indention.
                        $array[] = str_repeat($indenter, $level).substr($source, $startedat, $taglenght);
                    }
                    // If the next tag is "<", just advance pointer and let the tag indenter take care of it.
                } else {
                    $pt++;
                }
                // If the next letter doesnt exist... Were done... well, almost..
            } else {
                break;
            }
        }
        // Replace old source with the new one we just collected into our array.
        $source = implode($array, "\n");
        return $source;
    }

    public function importpreprocess() {
        global $CFG;

        print_error('cannotimportformat', 'question');
    }

    public function exportpreprocess() {
        global $CFG;

        require_once("/smarty2/libs/Smarty.class.php");

        // Assign the language for the export: by parameter, SESSION, USER, or the default of 'en'.
        $lang = current_language();
        $this->lang = $lang;

        return parent::exportpreprocess();
    }


    public function export_file_extension() {
        // Override default type so extension is .zip.
        return ".zip";
    }

    /**
     * Take a string, and wrap it in a CDATA section, if that is required to make
     * the output XML valid (copied from qformat_xml).
     * @param string $string a string
     * @return string the string, wrapped in CDATA if necessary.
     */
    public function xml_escape($string) {
        if (!empty($string) && htmlspecialchars($string) != $string) {
            // If the string contains something that looks like the end
            // of a CDATA section, then we need to avoid errors by splitting
            // the string between two CDATA sections.
            $string = str_replace(']]>', ']]]]><![CDATA[>', $string);
            return "<![CDATA[{$string}]]>";
        } else {
            return $string;
        }
    }

    /**
     * Copies all files needed by the questions to the given $path, and flattens the file names
     *
     * @param array $questions the question objects
     * @param string $path the full path name to where the media files need to be copied
     * @param string
     * @return bool
     */

    public function copy_resources($questions, $dir) {
        // Iterate through questions.
        foreach ($questions as $question) {
            $this->copy_files($question->contextid, 'question', 'questiontext', $question->id, $question->id, $dir);
            $this->copy_files($question->contextid, 'question', 'generalfeedback', $question->id, $question->id, $dir);
            if (!empty($question->options->answers)) {
                foreach ($question->options->answers as $answer) {
                    $this->copy_files($question->contextid, 'question', 'answer', $question->id, $answer->id, $dir);
                    $this->copy_files($question->contextid, 'question', 'answerfeedback', $question->id, $answer->id, $dir);
                }
            }
            if (!empty($question->hints)) {
                foreach ($question->hints as $hint) {
                    $this->copy_files($question->contextid, 'question', 'hint', $question->id, $hint->id, $dir);
                }
            }
            // The rest of the files to copy depends on question type.
            switch($question->qtype) {
                case 'numerical':
                    $this->copy_files($question->contextid, 'question', 'instruction', $question->id, $question->id, $dir);
                    break;
                case 'match':
                    if (!empty($question->options->subquestions)) {
                        foreach ($question->options->subquestions as $subquestion) {
                            $this->copy_files($question->contextid, 'qtype_match', 'subquestion', $question->id, $subquestion->id, $dir);
                        }
                    }
                    break;
                case 'essay':
                    $this->copy_files($question->contextid, 'qtype_essay', 'graderinfo', $question->id, $question->id, $dir);
                    break;
            }
        }

    }

    public function copy_files($contextid, $component, $area, $questionid, $itemid, $dir) {
        global $CFG;

        $destination = $dir . '/resources/' . $questionid . '/'. $component . '/' . $area . '/' . $itemid;

        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, $component, $area, $itemid);
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }
            if (!$directorycreated) {
                make_temp_directory($destination);
                $directorycreated = true;
            }
            $file->copy_content_to($CFG->tempdir . '/' . $destination . '/' . $file->get_filename());
        }
    }

    /**
     * exports the questions in a question category to the given location
     *
     * The parent class method was overridden because the IMS export consists of multiple files
     *
     * @param string $filename the directory name which will hold the exported files
     * @return bool - or errors out
     */
    public function exportprocess() {
        global $CFG, $OUTPUT, $USER, $DB;

        $courseid = $this->course->id;
        // Continue path for following error checks.
        $continuepath = "{$CFG->wwwroot}/question/export.php?courseid={$courseid}";

        // Create a temporary directory for the exports (if not already existing).
        $uniquecode = time();
        $this->tempdir = 'qformat_qtitwo/' . $USER->id . '/' . $uniquecode;
        if (!$path = make_temp_directory($this->tempdir)) {
            throw new moodle_exception('cannotcreatepath', 'question', '', $path);
            print_error('cannotcreatepath', 'quiz', '', $continuepath);
        }

        // Get the questions (from database) in this category.
        $questions = get_questions_category( $this->category );

        $count = 0;

        // Create the imsmanifest file.
        $smarty =& $this->init_smarty();
        $this->add_qti_info($questions);

        $manifestquestions = $this->objects_to_array($questions);
        $manifestid = str_replace(array(':', '/'), array('-', '_'), "question_category_{$this->category->id}---{$CFG->wwwroot}");
        $smarty->assign('externalfiles', 1);
        $smarty->assign('manifestidentifier', $manifestid);
        $smarty->assign('quiztitle', "question_category_{$this->category->id}");
        $smarty->assign('quizinfo', "All questions in category {$this->category->id}");
        $smarty->assign('questions', $manifestquestions);
        $smarty->assign('lang', $this->lang);
        $smarty->error_reporting = 99;
        $expout = $smarty->fetch('imsmanifest.tpl');
        $filepath = $path . '/imsmanifest.xml';
        if (empty($expout)) {
            print_error('emptyxml', 'question', $continuepath);
        }
        if (!$fh = fopen($filepath, "w")) {
            print_error('cannotopenforwriting', 'question', '', $continuepath);
        }
        if (!fwrite($fh, $expout)) {
            print_error('cannotwriteto', 'question', '', $continuepath);
        }
        fclose($fh);

        $this->xml_entitize($questions);

        // Iterate through questions.
        foreach ($questions as $question) {
            // Do not export hidden questions.
            if (!empty($question->hidden)) {
                continue;
            }

            // Do not export random questions.
            if ($question->qtype == 'random') {
                continue;
            }

            // used by file api
            $contextid = $DB->get_field('question_categories', 'contextid',
                    array('id' => $question->category));
            $question->contextid = $contextid;

            // Results are first written into string (and then to a file).
            $count++;

            $expout = $this->writequestion($question , null, true, $path) . "\n";
            $expout = $this->presave_process($expout );

            $filepath = $path.'/'.$this->get_assesment_item_id($question) . ".xml";
            if (!$fh = fopen($filepath, "w")) {
                print_error('cannotopenforwriting', 'question', '', $continuepath);
            }
            if (!fwrite($fh, $expout)) {
                print_error('cannotwriteto', 'question', '', $continuepath);
            }
            fclose($fh);

        }

        $this->copy_resources($questions, $this->tempdir);

        // Get the list of files in directory.
        $filestemp = get_directory_list($path, '', false, true, true);
        $files = array();
        foreach ($filestemp as $file) {
            // Add zip paths and fs paths to all them.
            $files[$file] = $path . '/' . $file;
        }
        // Get the zip packer.
        $zippacker = get_file_packer('application/zip');

        // Zip files.
        $zipfile = $path . ' /qti2.zip';
        $zippacker->archive_to_pathname($files, $zipfile);

        $zipcontent = file_get_contents($zipfile);

        // Remove the temporary directory.
        fulldelete($path);

        return $zipcontent;
    }

    /**
     * exports a quiz (as opposed to exporting a category of questions)
     *
     * The parent class method was overridden because the IMS export consists of multiple files
     *
     * @param object $quiz
     * @param array $questions - an array of question objects
     * @param object $result - if set, contains result of calling quiz_grade_responses()
     * @param string $redirect - a URL to redirect to in case of failure
     * @param string $submiturl - the URL for the qti player to send the results to (e.g. attempt.php)
     * @todo use $result in the ouput
     */
    public function export_quiz($course, $quiz, $questions, $result, $redirect, $submiturl = null) {
        $this->xml_entitize($course);
        $this->xml_entitize($quiz);
        $this->xml_entitize($questions);
        $this->xml_entitize($result);
        $this->xml_entitize($submiturl);
        if (!$this->exportpreprocess(0, $course)) {   // Do anything before that we need to.
            print_error('errorpreprocess', 'question', $redirect);
        }
        if (!$this->exportprocess_quiz($quiz, $questions, $result, $submiturl, $course)) {         // Process the export data.
            print_error('errorprocess', 'question', $redirect);
        }
        if (!$this->exportpostprocess()) {                    // In case anything needs to be done after.
            print_error('errorpostprocess', 'question', $redirect);
        }

    }


    /**
     * This function is called to export a quiz (as opposed to exporting a category of questions)
     *
     * @uses $USER
     * @param object $quiz
     * @param array $questions - an array of question objects
     * @param object $result - if set, contains result of calling quiz_grade_responses()
     * @todo use $result in the ouput
     */
    public function exportprocess_quiz($quiz, $questions, $result, $submiturl, $course) {
        global $USER;
        global $CFG;

        $gradingmethod = array (1 => 'GRADEHIGHEST',
                                2 => 'GRADEAVERAGE',
                                3 => 'ATTEMPTFIRST' ,
                                4 => 'ATTEMPTLAST');

        $questions = $this->quiz_export_prepare_questions($questions, $quiz->id, $course->id, $quiz->shuffleanswers);

        $smarty = $this->init_smarty();
        $smarty->assign('questions', $questions);

        // Quiz level smarty variables.
        $manifestid = str_replace(array(':', '/'), array('-', '_'), "quiz{$quiz->id}-{$CFG->wwwroot}");
        $smarty->assign('manifestidentifier', $manifestid);
        $smarty->assign('submiturl', $submiturl);
        $smarty->assign('userid', $USER->id);
        $smarty->assign('username', htmlspecialchars($USER->username, ENT_COMPAT, 'UTF-8'));
        $smarty->assign('quiz_level_export', 1);
        // Assigned specifically so as not to cause problems with category-level export.
        $smarty->assign('quiztitle', format_string($quiz->name, true));
        $smarty->assign('quiztimeopen', date('Y-m-d\TH:i:s', $quiz->timeopen)); // Ditto.
        $smarty->assign('quiztimeclose', date('Y-m-d\TH:i:s', $quiz->timeclose)); // Ditto.
        $smarty->assign('grademethod', $gradingmethod[$quiz->grademethod]);
        $smarty->assign('quiz', $quiz);
        $smarty->assign('course', $course);
        $smarty->assign('lang', $this->lang);
        $expout = $smarty->fetch('imsmanifest.tpl');
        return true;
    }

    /**
     * Prepares questions for quiz export
     *
     * The questions are changed as follows:
     *   - the question answers atached to the questions
     *   - image set to an http reference instead of a file path
     *   - qti specific info added
     *   - exporttext added, which contains an xml-formatted qti assesmentItem
     *
     * @param array $questions - an array of question objects
     * @param int $quizid
     * @return an array of question arrays
     */
    public function quiz_export_prepare_questions($questions, $quizid, $courseid, $shuffleanswers = null) {
        global $CFG;
        // Add the answers to the questions and format the image property.
        foreach ($questions as $key => $question) {
            $questions[$key] = get_question_data($question);
            $questions[$key]->courseid = $courseid;
            $questions[$key]->quizid = $quizid;
        }

        $this->add_qti_info($questions);
        $questions = $this->questions_with_export_info($questions, $shuffleanswers);
        $questions = $this->objects_to_array($questions);
        return $questions;
    }

    /**
     * calls htmlspecialchars for each string field, to convert, for example, & to &amp;
     *
     * collections are processed recursively
     *
     * @param array $collection - an array or object or string
     */
    public function xml_entitize(&$collection) {
        if (is_array($collection)) {
            foreach ($collection as $key => $var) {
                if (is_string($var)) {
                    $collection[$key] = htmlspecialchars($var, ENT_COMPAT, 'UTF-8');
                } else if (is_array($var) || is_object($var)) {
                    $this->xml_entitize($collection[$key]);
                }
            }
        } else if (is_object($collection)) {
            $vars = get_object_vars($collection);
            foreach ($vars as $key => $var) {
                if (is_string($var)) {
                    $collection->$key = htmlspecialchars($var, ENT_COMPAT, 'UTF-8');
                } else if (is_array($var) || is_object($var)) {
                    $this->xml_entitize($collection->$key);
                }
            }
        } else if (is_string($collection)) {
            $collection = htmlspecialchars($collection, ENT_COMPAT, 'UTF-8');
        }
    }

    /**
     * adds exporttext property to the questions
     *
     * Adds the qti export text to the questions
     *
     * @param array $questions - an array of question objects
     * @return an array of question objects
     */
    public function questions_with_export_info($questions, $shuffleanswers = null) {
        $exportquestions = array();
        foreach ($questions as $key => $question) {
            $expout = $this->writequestion( $question , $shuffleanswers) . "\n";
            $expout = $this->presave_process( $expout );
            $key = $this->get_assesment_item_id($question);
            $exportquestions[$key] = $question;
            $exportquestions[$key]->exporttext = $expout;
        }
        return $exportquestions;
    }

    /**
     * Creates the export text for a question
     *
     * @todo handle in-line media (specified in the question/subquestion/answer text) for course-level exports
     * @param object $question
     * @param bool $shuffleanswers whether or not to shuffle the answers
     * @param bool $courselevel whether or not this is a course-level export
     * @param string $path provide the path to copy question media files to, if $courselevel == true
     * @return string containing export text
     */
    public function writequestion($question, $shuffleanswers = null, $courselevel = false, $path = '') {
        // Turns question into string.
        // Question reflects database fields for general question and specific to type.
        global $CFG;
        $expout = '';
        $question->questiontext = str_replace('@@PLUGINFILE@@', 'resources/' . $question->id . '/question/questiontext/' . $question->id , $question->questiontext);

        if(isset($question->generalfeedback)){
            $question->generalfeedback = str_replace('@@PLUGINFILE@@', 'resources/' . $question->id . '/question/generalfeedback/' . $question->id , $question->generalfeedback);
        }
        if (!empty($question->options->answers)) {
            foreach ($question->options->answers as $key => $answer) {
                $question->options->answers[$key]->answer = str_replace('@@PLUGINFILE@@', 'resources/' . $question->id . '/question/answer/' . $answer->id, $answer->answer);
                $question->options->answers[$key]->feedback = str_replace('@@PLUGINFILE@@', 'resources/' . $question->id . '/question/answerfeedback/' . $answer->id, $answer->feedback);
            }
        }
        if (!empty($question->hints)) {
            foreach ($question->hints as $key => $hint) {
                $question->hints[$key]->hint = str_replace('@@PLUGINFILE@@', 'resources/' . $question->id . '/question/hint/' . $hint->id, $hint->hint);
            }
        }

        $hassize = empty($question->mediax) ? 0 : 1;

        // All other tags will be stripped from question text.
        $allowedtags = '<a><br><b><h1><h2><h3><h4><i><img><li><ol><strong><table><tr><td><th><u><ul><object>';
        $smarty =& $this->init_smarty();
        $assesmentitemid = $this->get_assesment_item_id($question);
        $questionid = "question{$question->id}" . $question->qtype;
        $smarty->assign('hassize', $hassize);
        $smarty->assign('questionid', $questionid);
        $smarty->assign('assessmentitemidentifier', $assesmentitemid);
        $smarty->assign('assessmentitemtitle', $question->name);
        $smarty->assign('courselevelexport', $courselevel);
        $smarty->assign('defaultmark', $question->defaultmark);

        if ($question->qtype == 'multianswer') {
            $question->questiontext = strip_tags($question->questiontext, $allowedtags . '<intro>');
            $smarty->assign('questionText',  $this->get_cloze_intro($question->questiontext));
        } else {
            $smarty->assign('questionText',  $question->questiontext);
        }

        $smarty->assign('question', $question);

        // Output depends on question type.
        switch($question->qtype) {
            case 'truefalse':
                $qanswers = $question->options->answers;
                $answers[0] = (array)$qanswers[$question->options->trueanswer];
                $answers[0]['answer'] = get_string('true', 'qtype_truefalse');
                $answers[1] = (array)$qanswers[$question->options->falseanswer];
                $answers[1]['answer'] = get_string('false', 'qtype_truefalse');

                if (!empty($shuffleanswers)) {
                    $answers = $this->shuffle_things($answers);
                }

                if (isset($question->response)) {
                    $correctresponseid = $question->response[$questionid];
                    if ($answers[0]['id'] == $correctresponseid) {
                        $correctresponse = $answers[0];
                    } else {
                        $correctresponse = $answers[1];
                    }
                } else {
                    $correctresponse = '';
                }

                $smarty->assign('correctresponse', $correctresponse);
                $smarty->assign('answers', $answers);
                $expout = $smarty->fetch('choice.tpl');
                break;
            case 'multichoice':
                $answers = $this->objects_to_array($question->options->answers);
                $correctresponses = $this->get_correct_answers($answers);
                $correctcount = count($correctresponses);
                $smarty->assign('responsedeclarationcardinality', $question->options->single ? 'single' : 'multiple');
                $smarty->assign('operator', $question->options->single ? 'match' : 'member');
                $smarty->assign('correctresponses', $correctresponses);
                $smarty->assign('answers', $answers);
                $smarty->assign('maxChoices', $question->options->single ? '1' : count($answers));
                $smarty->assign('shuffle', $question->options->shuffleanswers ? 'true' : 'false');
                $smarty->assign('generalfeedback', $question->generalfeedback);
                $smarty->assign('correctfeedback', $question->options->correctfeedback);
                $smarty->assign('partiallycorrectfeedback', $question->options->partiallycorrectfeedback);
                $smarty->assign('incorrectfeedback', $question->options->incorrectfeedback);
                $expout = $smarty->fetch('choiceMultiple.tpl');
                break;
            case 'shortanswer':
                $answers = $this->objects_to_array($question->options->answers);
                if (!empty($shuffleanswers)) {
                    $answers = $this->shuffle_things($answers);
                }

                $correctresponses = $this->get_correct_answers($answers);
                $correctcount = count($correctresponses);

                $smarty->assign('responsedeclarationcardinality', $correctcount > 1 ? 'multiple' : 'single');
                $smarty->assign('correctresponses', $correctresponses);
                $smarty->assign('answers', $answers);
                $smarty->assign('usecase', $question->options->usecase ? 'true' : 'false');
                $expout = $smarty->fetch('textEntry.tpl');
                break;
            case 'numerical':
                $question->instruction = str_replace('@@PLUGINFILE@@', 'resources/' . $question->id . '/question/instruction/' . $question->id , $question->instruction);
                $qanswer = array_pop( $question->options->answers );
                $smarty->assign('lowerbound', $qanswer->answer - $qanswer->tolerance);
                $smarty->assign('upperbound', $qanswer->answer + $qanswer->tolerance);
                $smarty->assign('answer', $qanswer->answer);
                $expout = $smarty->fetch('numerical.tpl');
                break;
            case 'match':
                if (!empty($question->options->subquestions)) {
                    foreach ($question->options->subquestions as $key => $subquestion) {
                        $question->options->subquestions[$key]->questiontext = str_replace('@@PLUGINFILE@@', 'resources/' . $question->id . '/qtype_match/subquestion/' . $subquestion->id, $subquestion->questiontext);
                    }
                }
                $subquestions = $this->objects_to_array($question->options->subquestions);
                if (!empty($shuffleanswers)) {
                    $subquestions = $this->shuffle_things($subquestions);
                }
                $setcount = count($subquestions);

                $smarty->assign('setcount', $setcount);
                $smarty->assign('matchsets', $subquestions);
                $smarty->assign('shuffle', $question->options->shuffleanswers ? 'true' : 'false');
                $expout = $smarty->fetch('match.tpl');
                break;
            case 'description':
                $expout = $smarty->fetch('extendedText.tpl');
                break;
            case 'essay':
                $expout = $smarty->fetch('extendedText_simpleEssay.tpl');
                break;
            // Loss of get_answers() from quiz_embedded_close_qtype class during
            // Gustav's refactor breaks multianswer export code badly - one for another day!!
            /*
            case 'multianswer':
                $answers = $this->get_cloze_answers_array($question);
                $questions = $this->get_cloze_questions($question, $answers, $allowedtags);

                $smarty->assign('cloze_trailing_text_id', CLOZE_TRAILING_TEXT_ID);
                $smarty->assign('answers', $answers);
                $smarty->assign('questions', $questions);
                $expout = $smarty->fetch('composite.tpl');
                break; */
            default:
                $smarty->assign('questionText', "This question type (Unknown: type " . $question->qtype . ")  has not yet been implemented");
                $expout = $smarty->fetch('notimplemented.tpl');
        }

        // Run through xml tidy function
        // $tidy_expout = $this->indent_xhtml( $expout, '    ' ) . "\n\n";
        // return $tidy_expout;
        return $expout;
    }

    /**
     * Gets an id to use for a qti assesment item
     *
     * @param object $question
     * @return string containing a qti assesment item id
     */
    public function get_assesment_item_id($question) {
        return "question{$question->id}";
    }

    /**
     * gets the answers whose grade fraction > 0
     *
     * @param array $answers
     * @return array (0-indexed) containing the answers whose grade fraction > 0
     */
    public function get_correct_answers($answers) {
        $correctanswers = array();
        foreach ($answers as $answer) {
            if ($answer['fraction'] > 0) {
                $correctanswers[] = $answer;
            }
        }
        return $correctanswers;
    }

    /**
     * gets a new Smarty object, with the template and compile directories set
     *
     * @return object a smarty object
     */
    public function & init_smarty() {
        global $CFG;

        // Create smarty compile dir in dataroot.
        $path = $CFG->dataroot."/smarty_c";
        if (!is_dir($path)) {
            if (!mkdir($path, $CFG->directorypermissions)) {
                print_error('cannotcreatepath', 'question', '', $path);
            }
        }
        $smarty = new Smarty;
        $smarty->template_dir = "{$CFG->dirroot}/question/format/qtitwo/templates/claroline";
        $smarty->compile_dir  = "$path";
        return $smarty;
    }

    /**
     * converts an array of objects to an array of arrays (not recursively)
     *
     * @param array $objectarray
     * @return array - an array of answer arrays
     */
    public function objects_to_array($objectarray) {
        $arrayarray = array();
        foreach ($objectarray as $object) {
            $arrayarray[] = (array)$object;
        }
        return $arrayarray;
    }

    /**
     * gets a question's cloze answer objects as arrays containing only arrays and basic data types
     *
     * @param object $question
     * @return array - an array of answer arrays
     */
    public function get_cloze_answers_array($question) {
        $answers = $this->get_answers($question);
        $this->xml_entitize($answers);
        foreach ($answers as $answerkey => $answer) {
            $answers[$answerkey]->subanswers = $this->objects_to_array($answer->subanswers);
        }
        return $this->objects_to_array($answers);
    }

    /**
     * gets an array with text and question arrays for the given cloze question
     *
     * To make smarty processing easier, the returned text and question sub-arrays have an equal number of elements.
     * If it is necessary to add a dummy element to the question sub-array, the question will be given an id of CLOZE_TRAILING_TEXT_ID.
     *
     * @param object $question
     * @param array $answers - an array of arrays containing the question's answers
     * @param string $allowabletags - tags not to strip out of the question text (e.g. '<i><br>')
     * @return array with text and question arrays for the given cloze question
     */
    public function get_cloze_questions($question, $answers, $allowabletags) {
        $questiontext = strip_tags($question->questiontext, $allowabletags);
        if (preg_match_all('/(.*){#([0-9]+)}/U', $questiontext, $matches)) {
            // matches[1] contains the text inbetween the question blanks
            // matches[2] contains the id of the question blanks (db: question_multianswer.positionkey)

            // Find any trailing text after the last {#XX} and add it to the array.
            if (preg_match('/.*{#[0-9]+}(.*)$/', $questiontext, $tail)) {
                $matches[1][] = $tail[1];
                $tailadded = true;
            }
            $questions['text'] = $matches[1];
            $questions['question'] = array();
            foreach ($matches[2] as $key => $questionid) {
                foreach ($answers as $answer) {
                    if ($answer['positionkey'] == $questionid) {
                        $questions['question'][$key] = $answer;
                        break;
                    }
                }
            }
            if ($tailadded) {
                // To have a matching number of question and text array entries:
                $questions['question'][] = array('id' => CLOZE_TRAILING_TEXT_ID, 'answertype' => 'shortanswer');
            }

        } else {
            $questions['text'][0] = $question->questiontext;
            $questions['question'][0] = array('id' => CLOZE_TRAILING_TEXT_ID, 'answertype' => 'shortanswer');
        }

        return $questions;
    }

    /**
     * strips out the <intro>...</intro> section, if any, and returns the text
     *
     * changes the text object passed to it.
     *
     * @param string $&text
     * @return string the intro text, if there was an intro tag. '' otherwise.
     */
    public function get_cloze_intro(&$text) {
        if (preg_match('/(.*)?\<intro>(.+)?\<\/intro>(.*)/s', $text, $matches)) {
            $text = $matches[1] . $matches[3];
            return $matches[2];
        } else {
            return '';
        }
    }


    /**
     * adds qti metadata properties to the questions
     *
     * The passed array of questions is altered by this function
     *
     * @param &questions an array of question objects
     */
    public function add_qti_info(&$questions) {
        foreach ($questions as $key => $question) {
            $questions[$key]->qtiinteractiontype = $this->get_qti_interaction_type($question->qtype);
            $questions[$key]->qtiscoreable = $this->get_qti_scoreable($question);
            $questions[$key]->qtisolutionavailable = $this->get_qti_solution_available($question);
        }
    }

    /**
     * returns whether or not a given question is scoreable
     *
     * @param object $question
     * @return bool
     */
    public function get_qti_scoreable($question) {
        switch ($question->qtype) {
            case 'description':
                return 'false';
            default:
                return 'true';
        }
    }

    /**
     * returns whether or not a solution is available for a given question
     *
     * The results are based on whether or not Moodle stores answers for the given question type
     *
     * @param object $question
     * @return bool
     */
    public function get_qti_solution_available($question) {
        switch($question->qtype) {
            case 'truefalse':
                return 'true';
            case 'multichoice':
                return 'true';
            case 'shortanswer':
                return 'true';
            case 'numerical':
                return 'true';
            case 'match':
                return 'true';
            case 'description':
                return 'false';
            case 'multianswer':
                return 'true';
            default:
                return 'true';
        }

    }

    /**
     * maps a moodle question type to a qti 2.0 question type
     *
     * @param string questiontype - the moodle question type
     * @return string qti 2.0 question type
     */
    public function get_qti_interaction_type($questiontype) {
        switch( $questiontype ) {
            case 'truefalse':
                $name = 'choiceInteraction';
                break;
            case 'multichoice':
                $name = 'choiceInteraction';
                break;
            case 'shortanswer':
                $name = 'textInteraction';
                break;
            case 'numerical':
                $name = 'textInteraction';
                break;
            case 'match':
                $name = 'matchInteraction';
                break;
            case 'description':
                $name = 'extendedTextInteraction';
                break;
            case 'multianswer':
                $name = 'textInteraction';
                break;
            default:
                $name = 'textInteraction';
        }
        return $name;
    }

    /**
     * returns the given array, shuffled
     *
     *
     * @param array $things
     * @return array
     */
    public function shuffle_things($things) {
        $things = swapshuffle_assoc($things);
        $oldthings = $things;
        $things = array();
        foreach ($oldthings as $key => $value) {
            $things[] = $value;      // This loses the index key, but doesn't matter.
        }
        return $things;
    }

}
