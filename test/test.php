<?php 

require( dirname(__DIR__) . "/src/ModelUtils.php");
use ModelUtils\ModelUtils as ModelUtils;

$mdl =<<<EOT
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

$model =json_decode(trim($mdl),TRUE);
$dct = '{"name": "Mehmet","birthday": "1980-01-01","gender":"G",  "experience": 4, "profile": {"weight":60.0,"age": 37,"pets": {"cat": 2}}}';
$doc =json_decode($dct,TRUE);

$doc = ModelUtils::fit_doc_to_model($model, $doc);
$doc = ModelUtils::setting_model_defaults($model, $doc);
var_dump(ModelUtils::validate_doc($model, $doc));

