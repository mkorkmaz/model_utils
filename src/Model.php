<?php

namespace ModelUtils;

class Model extends ModelUtils
{
    public $configYaml = "";
    public $schema = [];
    public $type = "basic"; // Possible options are basic, cache, search
    public $collectionName = "";
    public $dataFile = null;


    public function __construct()
    {
        $this->create();
    }

    public function create()
    {
        $config = yaml_parse(trim($this->configYaml));
        $this->schema = $config['schema'];
        $this->collectionName = $config['collection_name'];
        $this->type = (isset($config['type'])) ? $config['type'] : "basic";
        $this->dataFile = (isset($config['data_file'])) ? $config['data_file'] : null;
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

    public function install($dbConn)
    {
        $dbConn->drop($this->collectionName, $this->schema);
        $dbConn->create($this->collectionName, $this->schema);
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
        if ($this->dataFile !== null) {
            if (file_exists(BASE_DIR.$this->dataFile)) {
                $data = json_decode(file_get_contents(BASE_DIR.$this->dataFile), true);
                foreach ($data as $item) {
                    $item = $this->setModelDefaults($this->schema, $item);
                    $doc = $this->validateDoc($this->schema, $item);
                    $dbConn->insert($this->collectionName, $doc);
                }
            }
        }
        if (count($indexes)>0) {
            $dbConn->createIndexes($this->collectionName, $indexes);
        }
    }
}
