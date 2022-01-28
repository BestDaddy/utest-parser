<?php

namespace App\Services\ParserService\Parsers;

use App\Services\ParserService\ProcessParser;
use App\Services\ParserService\WordFileReader;

class UtestSimpleParser extends WordFileReader implements ProcessParser
{
    private function appendAnswer(string $content, &$answers) {
        $answers[] = array(
            'content'  => trim($content, '@ #'),
            'is_right' => empty($answers),
        );
    }

    private function parseTextTypeSimple($text): array
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
            if (str_contains($line, '#') && !empty($question_content)) {
                if ($answer_content != '') {
                    $this->appendAnswer($answer_content, $answers);
                }

                $answer_content     = $line;
                $question_going     = false;
                $answer_going       = true;
                continue;
            }
            elseif (str_contains($line, '@')) {
                if ($question_content != '' && !empty($answers)) {
                    if ($answer_content != '') {
                        $this->appendAnswer($answer_content, $answers);
                    }
                    $question = [
                        'content' => trim($question_content, '@ #'),
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

            if ($question_going) {
                $question_content = $question_content . $line;
            }
            elseif ($answer_going) {
                $answer_content   = $answer_content .  $line;
            }
        }

        return $result;
    }

    public function process($file) {
        switch ($ext = pathinfo($file, PATHINFO_EXTENSION)) {
            case ($ext == 'doc') :
                $text = $this->readDoc($file);
                break;
            case ($ext == 'docx') :
                $text = $this->readDocxPhpWord($file);
                break;
            default :
                return  response()->json([
                    'error' => 'Could not parse a file'
                ]);
        }
        return $this->parseTextTypeSimple($text);
    }
}
