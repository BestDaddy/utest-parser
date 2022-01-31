<?php

namespace App\Services\ParserService\Parsers;

use App\Services\ParserService\ProcessParser;
use App\Services\ParserService\WordFileReader;

class UtestSimpleParser extends WordFileReader implements ProcessParser
{
    protected function appendAnswer(string $content, &$answers) {
        $answers[] = array(
            'content'  => trim($content, '@ #'),
            'is_right' => empty($answers),
        );
    }

    public function process($file, $ext = 'docx') {
        switch ($ext) {
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
        $text .= "\n@ asdf";
        return $this->parseText($text, '/^[@]/', '/[#]/');
    }
}
