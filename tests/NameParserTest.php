<?php

namespace tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Xyluz\Street\Consts;
use Xyluz\Street\NameParser;

class NameParserTest extends TestCase
{

    protected NameParser $parser;

    public function setUp(): void
    {
        $this->parser = new NameParser();
    }

    public function test_instance()
    {
        $this->assertInstanceOf(NameParser::class, $this->parser);
    }

    public function test_parser_set_import_works(){

        $this->assertEmpty($this->parser->path);
        $this->parser->setImportPath('import-path');
        $this->assertNotEmpty($this->parser->path);

    }

    public static function data_file_load_provider(): \Generator
    {

        yield 'loader throws exception if path is set to empty string' => [
            'path'=>'',
            'expect'=>\InvalidArgumentException::class,
        ];

        yield 'loader throws exception if files does not exist' => [
            'path'=>'iamfile.csv',
            'expect'=>\InvalidArgumentException::class,
        ];

    }

    /**
     * @return void
     */
    #[DataProvider('data_file_load_provider')]
    public function test_parser_throws_exception_if_path_does_not_exist(
        string $path,
        string $expect
    ){

        $this->expectException($expect);
        $this->parser->setImportPath($path)->loadFile();

    }


    public function test_parser_loads_files_with_import_path(){

        $contentLoaded = $this->parser->setImportPath( Consts::CORRECT_FILE_PATH)->loadFile();

        $this->assertNotEmpty($contentLoaded);
        $this->assertCount(15, $contentLoaded);

    }

    public static function data_name_component_extractor_provider(): \Generator
    {
        yield 'given first_name, last_name and title' => [
            'name'=>'Mr Seyi Onifade',
            'expect'=>[
                'title'=> 'Mr',
                'initial'=> null,
                'first_name'=>'Seyi',
                'last_name'=>'Onifade'
            ],
        ];

        yield 'given first_name, last_name, title and initial with no .' => [
            'name'=>'Mr Y Seyi Onifade',
            'expect'=>[
                'title'=> 'Mr',
                'initial'=>'Y',
                'first_name'=>'Seyi',
                'last_name'=>'Onifade'
            ],
        ];

        yield 'given first_name, last_name, title and initial with .' => [
            'name'=>'Mr Y. Seyi Onifade',
            'expect'=>[
                'title'=> 'Mr',
                'initial'=>'Y',
                'first_name'=>'Seyi',
                'last_name'=>'Onifade'
            ],
        ];

        yield 'given single name with no first_name' => [
            'name'=>'Mr Onifade',
            'expect'=>[
                'title'=> 'Mr',
                'initial'=> null,
                'first_name'=>null,
                'last_name'=>'Onifade'
            ],
        ];

        yield 'given single name with all paramters' => [
            'name'=>'Mr Seyi Onifade',
            'expect'=>[
                'title'=> 'Mr',
                'initial'=>null,
                'first_name'=>'Seyi',
                'last_name'=> 'Onifade'
            ],
        ];

        yield 'given multiple titles with connector &, and single last_name' => [
            'name'=>'Mr & Mrs Seyi',
            'expect'=> [
                [
                    'title'=> 'Mr',
                    'initial'=>null,
                    'first_name'=>null,
                    'last_name'=>'Seyi',
                ],
                [
                    'title'=> 'Mrs',
                    'initial'=>null,
                    'first_name'=>null,
                    'last_name'=>'Seyi',
                ]
            ],
        ];

        yield 'given multiple titles with connector and, and single last_name' => [
            'name'=>'Mr and Mrs Seyi',
            'expect'=> [
                [
                    'title'=> 'Mr',
                    'initial'=>null,
                    'first_name'=>null,
                    'last_name'=>'Seyi',
                ],
                [
                    'title'=> 'Mrs',
                    'initial'=>null,
                    'first_name'=>null,
                    'last_name'=>'Seyi',
                ]
            ],
        ];

        yield 'given multiple titles with connection &, first and last_name' => [
            'name'=>'Mr and Mrs Seyi Onifade',
            'expect'=> [
                [
                    'title'=> 'Mr',
                    'initial'=>null,
                    'first_name'=>'Seyi',
                    'last_name'=>'Onifade',
                ],
                [
                    'title'=> 'Mrs',
                    'initial'=>null,
                    'first_name'=>'Seyi',
                    'last_name'=>'Onifade',
                ]
            ],
        ];

        yield 'given two unique names with connector and' => [
            'name'=>'Mr Seyi Onifade and Mrs Mike Smith',
            'expect'=> [
                [
                    'title'=> 'Mr',
                    'initial'=>null,
                    'first_name'=>'Seyi',
                    'last_name'=>'Onifade',
                ],
                [
                    'title'=> 'Mrs',
                    'initial'=>null,
                    'first_name'=>'Mike',
                    'last_name'=>'Smith',
                ]
            ],
        ];


    }


    #[dataProvider('data_name_component_extractor_provider')]
    public function test_parser_extract_name_component_class(
        string $name,
        array $expect
    ){

        $result = $this->parser->extractNameComponents($name);
        $this->assertEquals($expect, $result);
        $this->assertSame($result['first_name'],$expect['first_name']);
        $this->assertSame($result['last_name'],$expect['last_name']);
        $this->assertSame($result['title'],$expect['title']);
        $this->assertSame($result['initials'],$expect['initials']);

    }


    public function test_parser_loads_and_parses_csv_file(){

        $result = $this->parser->setImportPath( Consts::CORRECT_FILE_PATH)->run();

        $this->assertCount(15,$result);
        $this->assertIsArray($result);

    }

}
