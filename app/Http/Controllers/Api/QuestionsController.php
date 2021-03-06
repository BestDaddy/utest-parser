<?php

namespace App\Http\Controllers\Api;

use App\Domain\Contracts\ParserTypeContract;
use App\Http\Controllers\Controller;
use App\Services\ParserService\Parsers\ComplexParser;
use App\Services\ParserService\Parsers\DarkanDalaParser;
use App\Services\ParserService\Parsers\UtestSimpleParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuestionsController extends Controller
{
    public function parseTypes() {
        return array(
            [
                'id' => ParserTypeContract::UTEST_PARSER_ID,
                'name' => 'UTEST: @ - вопрос # - ответ'
            ],
            [
                'id' => ParserTypeContract::DARKANDALA_PARSER_ID,
                'name' => 'Darkandala: ПРОТОКОЛ ИСПЫТАНИЯ ПОЧВЫ '
            ],
            [
                'id' => ParserTypeContract::OLYMPIC_TEST_PARSER_ID,
                'name' => 'Олимп тесты'
            ]
        );
    }

    public function parse(Request $request) {
        $error = Validator::make($request->all(), array(
            'parser_type_id' => ['required'],
            'file' => ['required', 'file']
        ));

        if($error->fails()) {
            return response()->json(['errors' => $error->errors()->all()]);
        }

        $type = $request->input('parser_type_id');

        if($type == ParserTypeContract::UTEST_PARSER_ID) {
            $parser = new UtestSimpleParser();
        }
        elseif ($type == ParserTypeContract::OLYMPIC_TEST_PARSER_ID) {
            $parser = new ComplexParser();
        }
        elseif ($type == ParserTypeContract::DARKANDALA_PARSER_ID) {
            $parser = new DarkanDalaParser();
        }
        else {
            return  response()->json([
                'error' => 'Unknown parser type'
            ]);
        }
        $result =  $parser->process($request->file('file'), $request->file('file')->extension());
        return response()->json($result);
    }
}
