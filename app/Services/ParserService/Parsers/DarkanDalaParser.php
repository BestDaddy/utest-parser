<?php

namespace App\Services\ParserService\Parsers;

use App\Services\ParserService\ProcessParser;

class DarkanDalaParser implements ProcessParser
{
    private function parseGround($file) {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $highest = $spreadsheet->getActiveSheet()->getHighestRow('A');
        $result = array(
            'name' => $spreadsheet->getActiveSheet()->getCell('G11')->getValue(),
            'selection_date' => $spreadsheet->getActiveSheet()->getCell('F18')->getValue(),
            'indicators' => array(),
        );

        foreach (range('F', 'K') as $column) {
            $indicator = [
                'name' => $spreadsheet->getActiveSheet()->getCell($column . 27)->getValue(),
                'places' => array(),
            ];
            $field = null;
            foreach (range(31, ( $spreadsheet->getActiveSheet()->getHighestRow('A') - 17)) as $row) {
                $field = $spreadsheet->getActiveSheet()->getCell('C' . $row)->getValue() ?: $field;
                $indicator['places'][] = [
                    'id' => $spreadsheet->getActiveSheet()->getCell('B' . $row)->getValue(),
                    'grid' => $spreadsheet->getActiveSheet()->getCell('D' . $row)->getValue(),
                    'field' => $field,
                    'value' => $spreadsheet->getActiveSheet()->getCell($column . $row)->getValue(),
                ];
            }
            $result['indicators'][] = $indicator;
        }

        return $result;
    }


    public function process($file) {
        return $this->parseGround($file);
    }
}
