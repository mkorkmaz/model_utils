<?php
/**
 *
 *
 * @TODO : Use DataProvider for tests
 * @TODO : Check model return data types (int, string, lentgth, ...)
 * @TODO : Add some failure statuses
 */
namespace tests;

use ModelUtils\ModelUtils;

class ModelUtilsTest extends \PHPUnit_Framework_TestCase
{
    protected $testData = null;

    protected function setUp()
    {
        $this->testData =<<<EOT
{
    "name": {"_type": "string", "_min_length": 3, "_max_length": 128, "_required": true, "_index": true, "_default": "", "_ref": null, "_has_many": 0},
    "email": {"_type": "string", "_input_type": "email", "_required": true, "_index": true, "_default": "", "_ref": null, "_has_many": 0},  
    "birthday": {"_type": "string", "_input_type": "date", "_required": true, "_index": true, "_default": "", "_ref": null, "_has_many": 0},
    "created": {"_type": "integer", "_input_type": "timestamp" , "_required": true, "_index": true, "_default":"now", "_ref": null, "_has_many": 0},
    "last_name": {"_type": "string", "_required": true, "_index": true, "_default": "", "_ref": null, "_has_many": 0},
    "experience": {"_type": "integer", "_required": true, "_index": true, "_default": 0, "_ref": null, "_has_many": 0},
    "gender": {"_type": "string", "_required": false, "_index": false, "_default": "N", "_ref": null, "_has_many": 0,"_in_options":["M","F","N"]},
    "profile": {
        "age": {"_type": "integer", "_required": false, "_index": false, "_min_length":30, "_default": 0, "_ref": null, "_has_many": 0},
        "weight": {"_type": "float", "_required": false, "_index": false, "_default": 75.0, "_max_length":120, "_ref": null, "_has_many": 0},
        "pets": {
            "cat": {"_type": "integer", "_required": false, "_index": false, "_default": 0, "_ref": null, "_has_many": 0},
            "dogs": {"_type": "integer", "_required": false, "_index": false, "_default": 0, "_ref": null, "_has_many": 0}
        }
    }
}
EOT;
    }

    /**
     * @dataProvider successDataProvider
     */
    public function testSuccessDocuments($dct)
    {
        $doc   =json_decode($dct, true);
        $model = json_decode(trim($this->testData), true);
        $doc   = ModelUtils::fit_doc_to_model($model, $doc);

        $this->assertInternalType('array', $doc);
        $this->assertArrayHasKey('name', $doc);
        $this->assertArrayHasKey('birthday', $doc);
        $this->assertArrayHasKey('gender', $doc);
        $this->assertArrayHasKey('experience', $doc);
        $this->assertArrayHasKey('profile', $doc);
        $this->assertInternalType('array', $doc['profile']);
        $this->assertArrayHasKey('weight', $doc['profile']);
        $this->assertArrayHasKey('age', $doc['profile']);
        $this->assertArrayHasKey('pets', $doc['profile']);
        $this->assertInternalType('array', $doc['profile']['pets']);
        $this->assertArrayHasKey('cat', $doc['profile']['pets']);

        $doc = ModelUtils::setting_model_defaults($model, $doc);
        $doc = ModelUtils::validate_doc($model, $doc);

        $this->assertArrayHasKey('dogs', $doc['profile']['pets']);
    }

    /**
     * @dataProvider failureDataProvider
     * @expectedException \Exception
     * @expectedException \RuntimeException
     */
    public function testFailureDocuments($dct)
    {
        $doc   = json_decode($dct, true);
        $model = json_decode(trim($this->testData), true);
        $doc   = ModelUtils::fit_doc_to_model($model, $doc);
        $doc   = ModelUtils::setting_model_defaults($model, $doc);
        $doc   = ModelUtils::validate_doc($model, $doc);
    }

    public function successDataProvider()
    {
        return [
            ['{"name": "Mehmet", "birthday": "1980-01-01", "gender":"G",  "experience": 4, "profile": {"weight":60.0,"age": 37,"pets": {"cat": 2}}}'],
        ];
    }

    public function failureDataProvider()
    {
        return [
            ['{"name": "Mehmet", "birthday": "19800101","gender":"G",  "experience": 4, "profile": {"weight":60.0,"age": 37,"pets": {"cat": 2}}}'],
        ];
    }
}
