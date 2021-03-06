<?php

namespace App\Services\ParserService;

use PhpOffice\PhpWord\Element\Image;
use PhpOffice\PhpWord\Element\Text;
use ZipArchive;

class WordFileReader
{
    public function readDoc($file)
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

    public function readDocxPhpWord($file): string
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($file);
        $content = '';
        $dir = time();
        if (!is_dir(storage_path('app/public/' . $dir))) {
            mkdir(storage_path('app/public/' . $dir));
        }
        foreach($phpWord->getSections() as $section) {
            foreach($section->getElements() as $element) {
                if (method_exists($element, 'getElements')) {
                    $is_right = false;
                    foreach($element->getElements() as $child_element) {
                        if ($child_element instanceof Image) {
                            $name = uniqid(). '.' .$child_element->getImageExtension();
                            file_put_contents(
                                storage_path('app/public/'. $dir . '/'. $name),
                                base64_decode($child_element->getImageStringData(true))
                            );
                            $content .= sprintf("%s%s%s\n", ' <img src="', asset('/storage/'. $dir . '/' . $name), '"> ');
                        }
                        elseif ($child_element instanceof Text) {
                            if (
                                $child_element->getFontStyle()->getFgColor()
                                || ($child_element->getFontStyle()->getColor() != "00000" && !is_null($child_element->getFontStyle()->getColor()))
                            ) {
                                $is_right = true;
                            }
                            $content .= $child_element->getText() . "";
                        }
                    }
                    $content .= $is_right ? " [right]\n" : "\n";
                }
            }
        }
        return $content;
    }

    public function readDocx($file) {
        $content = '';
        $zip = zip_open($file);
        $i = 0;
        $zip_archive =  new ZipArchive;
        $zip_archive->open($file);
        if (!$zip || is_numeric($zip)) return false;

        while ($zip_entry = zip_read($zip)) {
            if (zip_entry_open($zip, $zip_entry) == FALSE) continue;
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
            }
            $i++;

            if (zip_entry_name($zip_entry) != "word/document.xml") continue;

            $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
            zip_entry_close($zip_entry);
        }

        zip_close($zip);
        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        return strip_tags($content);
    }
}
