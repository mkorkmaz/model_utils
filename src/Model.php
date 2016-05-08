<?php

namespace ModelUtils;

class Model
{

    public $config_yaml = "";
    public $schema = [];
    public $type = "basic"; // Possible options are basic, cache, search
    public $collection_name = "";
    public $data_file = null;

    public function __construct()
    {
        $config = yaml_parse(trim($this->config_yaml));
        $this->schema = $config['schema'];
        $this->collection_name = $config['collection_name'];
        $this->type = (isset($config['type'])) ? $config['type'] : "basic";
        $this->data_file = (isset($config['data_file'])) ? $config['data_file'] : null;

    }

    public function validate($doc)
    {
        return ModelUtils::validateDoc($this->schema, $doc);
    }

    public function setDefaults($doc)
    {
        return ModelUtils::settingModelDefaults($this->schema, $doc);
    }


    public function fitDoc($doc)
    {
        return ModelUtils::fitDocToModel($this->schema, $doc);
    }

    public function install($db)
    {

        $db->drop($this->collection_name, $this->schema);
        $db->create($this->collection_name, $this->schema);
        $indexes = [];
        foreach ($this->schema as $field => $fconfig) {
            if ($fconfig['_index'] === true) {
                $index = ['key'=>[$field=>1]];
                if (isset($fconfig["_index_type"])) {
                    switch ($fconfig["_index_type"]) {
                        case 'unique':
                            $index['unique'] = true;
                            break;
                    }
                }
                $indexes[] = $index;
            }
        }
        if ($this->data_file !== null) {
            if (file_exists(BASE_DIR.$this->data_file)) {
                $data = json_decode(file_get_contents(BASE_DIR.$this->data_file), true);
                foreach ($data as $item) {
                    $item = ModelUtils::settingModelDefaults($this->schema, $item);
                    $doc = ModelUtils::validateDoc($this->schema, $item);
                    $db->insert($this->collection_name, $doc);
                }
            }
        }
        if (count($indexes)>0) {
            $db->createIndexes($this->collection_name, $indexes);
        }
    }
}
