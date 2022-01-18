<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\IOFactory;
use ZipArchive;

class QuestionsController extends Controller
{
    private $image_assets = [];

    private function read_doc($file)
    {
        $fileHandle = fopen($file, "r");
        $line = @fread($fileHandle, filesize($file));
        $lines = explode(chr(0x0D), $line);
        $outtext = "";
        foreach ($lines as $thisline) {
            $pos = strpos($thisline, chr(0x00));
            if (($pos !== FALSE) || (strlen($thisline) == 0)) {
            } else {
                $outtext .= $thisline . " \n";
            }
        }
        return preg_replace("/[^a-zA-Z0-9@#\s\,\.\-\n\r\t\/\_\(\)]/", "", $outtext);
    }

    private function read_docx_2($file) {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($file);
        $content = '';
        foreach($phpWord->getSections() as $section) {

            foreach($section->getElements() as $element) {
                if (method_exists($element, 'getElements')) {
                    foreach($element->getElements() as $child_element) {

                        if (method_exists($child_element, 'getImageStringData')) {
                            file_put_contents(
                                storage_path('/app/public/' . $child_element->getName()),
                                base64_decode($child_element->getImageStringData(true))
                            );
                            $content .= "%& <img>". asset('/storage/' . $child_element->getName()) . "</img>\n";
                        }
                        elseif (method_exists($child_element, 'getText')) {
                            $content .= $child_element->getText() . "\n";
                        }
                    }
                }
            }
        }
        return $content;
    }

    private function read_docx($file) {
        $content = '';
        $zip = zip_open($file);
        $i = 0;
        $zip_archive =  new ZipArchive;
        $zip_archive->open($file);
        if (!$zip || is_numeric($zip)) return false;

        while ($zip_entry = zip_read($zip)) {
            if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

//            if(preg_match("([^\s]+(\.(?i)(jpg|jpeg|png))$)", zip_entry_name($zip_entry)))
//            {
//                //  echo zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
//
////                echo "<image src='display.php?filename=".$filename."&index=".$index."' ><br />";
//            }


            if (strpos(zip_entry_name($zip_entry), 'word/media') !== false) {
                $image_name = substr(zip_entry_name($zip_entry), 11);
                # Prevent EMF file extensions passing, as they are used by word rather than being manually placed
                if (substr($image_name, -3) == 'emf') continue;

                $zip_element = $zip_archive->statIndex($i);

                $index = $zip_element['index'];
                # Place the image assets into an array for future reference

                $image = file_put_contents(storage_path('/app/public/' . $image_name), zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));

                $this->image_assets[$image_name] = array(
                    'title' => $image_name,
                    'position' => $index,
                    'path' =>  asset(('storage/app/public/' . $image_name)),
//                    'data' => base64_encode(zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)))
                );


//                dd(zip_entry_name($zip_entry));
//                $content .= 'KRWK: ' . storage_path('/app/public/' . $image_name);
//                zip_entry_close($zip_entry);
//                continue;
            }
            $i++;

            if (zip_entry_name($zip_entry) != "word/document.xml") continue;

            $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
            zip_entry_close($zip_entry);
        }

        zip_close($zip);
        dd($this->image_assets);
        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        return strip_tags($content);
    }

    private function appendAnswer(string $content, &$answers, $images = []) {
        $answers[] = array(
            'content'  => trim($content, '@ #'),
            'is_right' => empty($answers),
            'images'   => $images,
        );
    }

    private function parseText($text): array
    {
        $question_content = '';
        $answer_content   = '';

        $question_going = false;
        $answer_going   = false;

        $result = [
            'questions' => array(),
        ];
        $answers = array();
        $answer_images  = array();
        $question_images  = array();

        foreach(preg_split("/((\r?\n)|(\r\n?))/", $text) as $line){
            if (($question_going || $answer_going) && str_contains($line, '%&')) {
                if($answer_going) $answer_images[] = trim($line, '%& ');
                elseif($question_going) $question_images[] = trim($line, '%& ');
            }
            elseif (str_contains($line, '#') && !empty($question_content)) {
                if ($answer_content != '') {
                    $this->appendAnswer($answer_content, $answers, $answer_images);
                }

                $answer_content     = $line;
                $question_going     = false;
                $answer_going       = true;
                $answer_images      = array();
                continue;
            }
            elseif (str_contains($line, '@')) {
                if ($question_content != '' && !empty($answers)) {
                    if ($answer_content != '') {
                        $this->appendAnswer($answer_content, $answers, $answer_images);
                    }
                    $question = [
                        'content' => trim($question_content, '@ #'),
                        'answers' => $answers,
                        'images'  => $question_images,
                    ];
                    $result['questions'][] = $question;
                    $answer_content = '';
                    $answers = array();
                    $question_images  = array();
                }

                $question_content   = $line;
                $question_going     = true;
                $answer_going       = false;
                continue;
            }

            if ($question_going) {
                $question_content = $question_content . ' ' .  $line;
            }
            elseif ($answer_going) {
                $answer_content   = $answer_content . ' ' .  $line;
            }
        }

        return $result;
    }



    public function parse(Request $request) {
        $error = Validator::make($request->all(), array(
            'question_type' => ['required'],
            'file' => ['required', 'file']
        ));

        if($error->fails()) {
            return response()->json(['errors' => $error->errors()->all()]);
        }

        switch ($ext  = $request->file('file')->extension()) {
            case ($ext == 'doc') :
                $text = $this->read_doc($request->file('file'));
                break;
            case ($ext == 'docx') :
                $text = $this->read_docx_2($request->file('file'));
//                return  $text;
                break;
            default :
                return [
                    'error' => 'Could not parse a file'
                ];

        }

        return $this->parseText($text);
    }

//    public function parse(Request $request) {
//        $error = Validator::make($request->all(), array(
//            'question_type' => ['required'],
//            'file' => ['required', 'file']
//        ));
//
//        if($error->fails()) {
//            return response()->json(['errors' => $error->errors()->all()]);
//        }
//        $result = [
//            'questions' => array(),
//        ];
//
//        $answers = array();
//
//        $content = "";
//        $test = $this->read_docx($request->file('file'));
//        $i =0;
//
//        $question_content = '';
//        $answer_content   = '';
//
//        $question_going = false;
//        $answer_going   = false;
//
//
//        foreach(preg_split("/((\r?\n)|(\r\n?))/", $test) as $line){
//
//            if (str_contains($line, '#')) {
//
//                if($answer_content != '') {
//                    $answers[] = array(
//                        'content' => $answer_content,
//                    );
//                }
//
//                $answer_content     = $line;
//                $question_going     = false;
//                $answer_going       = true;
//                continue;
//            }
//            elseif(str_contains($line, '@')) {
//
//                if($question_content != '' && !empty($answers)) {
//                    if($answer_content != '') {
//                        $answers[] = array(
//                            'content' => $answer_content,
//                        );
//                    }
//                    $question = [
//                        'content' => $question_content,
//                        'answers' => $answers
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
//
//            if($question_going) {
//                $question_content = $question_content . ' ' . $line;
//            }
//            elseif($answer_going) {
//                $answer_content   = $answer_content . ' ' . $line;
//            }
//
//
//
//
//            $content = $content . ' ' . $line;
//            if($i >8) break;
//            $i++;
//        }
//
//        return $result;
//    }
}
