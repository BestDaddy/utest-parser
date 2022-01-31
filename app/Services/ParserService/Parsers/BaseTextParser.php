<?php

namespace App\Services\ParserService\Parsers;

abstract class BaseTextParser
{
    abstract protected function appendAnswer(string $content, &$answers);

    protected function parseText($text, $question_filter, $answer_filter): array
    {
        $question_content = '';
        $answer_content   = '';

        $question_going = false;
        $answer_going   = false;

        $result = [
            'questions' => array(),
        ];
        $answers = array();
        foreach(preg_split("/((\r?\n)|(\r\n?))/", $text) as $line){
            if (preg_match($question_filter, $line)) {
                if ($question_content != '' && !empty($answers)) {
                    if ($answer_content != '') {
                        $this->appendAnswer($answer_content, $answers);
                    }
                    $question = [
                        'content' => trim(preg_replace($question_filter, '', $question_content)),
                        'answers' => $answers,
                    ];
                    $result['questions'][] = $question;
                    $answer_content = '';
                    $answers = array();
                }

                $question_content   = $line;
                $question_going     = true;
                $answer_going       = false;
                continue;
            }
            elseif (preg_match($answer_filter, $line) && !empty($question_content)) {
                if ($answer_content != '') {
                    $this->appendAnswer($answer_content, $answers);
                }
                $answer_content     = $line;
                $question_going     = false;
                $answer_going       = true;
                continue;
            }

            if ($question_going) {
                $question_content = $question_content . $line;
            }
            elseif ($answer_going) {
                $answer_content   = $answer_content .  $line;
            }

        }

        return $result;
    }
}
