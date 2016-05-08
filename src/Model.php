<?php

namespace ModelUtils;

class Model extends ModelUtils
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
        return $this->validateDoc($this->schema, $doc);
    }

    public function setDefaults($doc)
    {
        return $this->setModelDefaults($this->schema, $doc);
    }


    public function fitDoc($doc)
    {
        return $this->fitDocToModel($this->schema, $doc);
    }

    public function install($db_conn)
    {
        $db_conn->drop($this->collection_name, $this->schema);
        $db_conn->create($this->collection_name, $this->schema);
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
                    $item = $this->setModelDefaults($this->schema, $item);
                    $doc = $this->validateDoc($this->schema, $item);
                    $db_conn->insert($this->collection_name, $doc);
                }
            }
        }
        if (count($indexes)>0) {
            $db_conn->createIndexes($this->collection_name, $indexes);
        }
    }
}
