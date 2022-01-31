<?php

namespace App\Services\ParserService\Parsers;

use App\Services\ParserService\ProcessParser;
use App\Services\ParserService\WordFileReader;

class ComplexParser extends WordFileReader implements ProcessParser
{
    protected function appendAnswer(string $content, &$answers) {
        $filter = array("[right]", "A)", "B)", "C)", "D)", "A.", "B.", "C.", "D.", "А.", "В.", "С.", "Д.", "А)", "В)", "С)", "Д)");
        $answers[] = array(
            'content'  => trim(str_replace($filter, "", $content)),
            'is_right' => str_contains($content, '[right]'),
        );
    }

//    private function parseComplexText($text): array
//    {
//        $question_content = '';
//        $answer_content   = '';
//
//        $question_going = false;
//        $answer_going   = false;
//
//        $result = [
//            'questions' => array(),
//        ];
//        $answers = array();
//        foreach(preg_split("/((\r?\n)|(\r\n?))/", $text) as $line){
//            if (preg_match('/^[0-9]+[.]|^[0-9]+[)]/', $line)) {
//                if ($question_content != '' && !empty($answers)) {
//                    if ($answer_content != '') {
//                        $this->appendAnswer($answer_content, $answers);
//                    }
//                    $question = [
//                        'content' => trim(preg_replace('/^[0-9]+[)]|^[0-9]+[.]/', '', $question_content)),
//                        'answers' => $answers,
//                    ];
//                    $result['questions'][] = $question;
//                    $answer_content = '';
//                    $answers = array();
//                }
//
//                $question_content   = $line;
//                $question_going     = true;
//                $answer_going       = false;
//                continue;
//            }
//            elseif (preg_match('/[A-DА-Д][.]|[A-DА-Д][)]/', $line) && !empty($question_content)) {
//                if ($answer_content != '') {
//                    $this->appendAnswer($answer_content, $answers);
//                }
//                $answer_content     = $line;
//                $question_going     = false;
//                $answer_going       = true;
//                continue;
//            }
//
//            if ($question_going) {
//                $question_content = $question_content . $line;
//            }
//            elseif ($answer_going) {
//                $answer_content   = $answer_content .  $line;
//            }
//
//        }
//
//        return $result;
//    }

    public function process($file): array
    {
        $text = $this->readDocxPhpWord($file);
        $text .= "\n999."; // КОСТЫЫЫЫЫЛЬ
        return $this->parseText($text, '/^[0-9]+[.]|^[0-9]+[)]/', '/[A-DА-Д][.]|[A-DА-Д][)]/');
    }
}
