<?php

namespace App\Services\ParserService\Parsers;

use App\Services\ParserService\ProcessParser;
use App\Services\ParserService\WordFileReader;

class UtestSimpleParser extends BaseTextParser implements ProcessParser
{
    private $wordReader;

    public function __construct() {
        $this->wordReader = new WordFileReader();
    }

    protected function appendAnswer(string $content, &$answers) {
        $answers[] = array(
            'content'  => trim($content, '@ #'),
            'is_right' => empty($answers),
        );
    }

    public function process($file, $ext = 'docx') {
        switch ($ext) {
            case ($ext == 'doc') :
                $text = $this->wordReader->readDoc($file);
                break;
            case ($ext == 'docx') :
                $text = $this->wordReader->readDocxPhpWord($file);
                break;
            default :
                return  response()->json([
                    'error' => 'Could not parse a file'
                ]);
        }
        $text .= "\n@ asdf";  // todo fix
        return $this->parseText($text, '/^[@]/', '/[#]/');
    }
}
