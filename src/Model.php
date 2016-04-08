<?php

namespace ModelUtils;

class Model{

    public $config = [];
    public $data_file = null;

    public function __construct(){
        $this->config = json_decode(trim($this->config_str), true);
    }

    public function validate($doc){
        return ModelUtils::validate_doc($this->config, $doc);
    }

    public function set_defaults($doc){
        return ModelUtils::setting_model_defaults($this->config, $doc);
    }


    public function fit_doc($doc){
        return ModelUtils::fit_doc_to_model($this->config, $doc);
    }

    public function install($db){

        $db->drop($this->collection_name, $this->config);
        $db->create($this->collection_name, $this->config);
        $indexes =[];
        foreach ($this->config as $field=>$fconfig){
            if($fconfig['_index']===true){
                $index=['key'=>[$field=>1]];
                if(isset($fconfig["_index_type"])){
                    switch ($fconfig["_index_type"]){
                        case 'unique':
                            $index['unique']=true;
                            break;
                    }
                }
                $indexes[]=$index;
            }
        }
        if($this->data_file !== null){
            if(file_exists(BASE_DIR.$this->data_file)){
                $data = json_decode(file_get_contents(BASE_DIR.$this->data_file),true);
                foreach ($data as $item){
                    $item = ModelUtils::setting_model_defaults($this->config, $item);
                    $doc = ModelUtils::validate_doc($this->config, $item);
                    $db->insert($this->collection_name,$doc);
                }
            }
        }
        if(count($indexes)>0){
            $db->create_indexes($this->collection_name, $indexes);
        }
    }
}