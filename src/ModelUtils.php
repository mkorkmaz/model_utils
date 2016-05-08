<?php
/**
 * ModelUtils: A simple PHP class for validating variable types, fixing, sanitising and setting default values for a
 * model definition encoded as an array .
 * *
 *  @TODO: A doc item can be array that has multiple values,
 *  implement validation and sanitization for the situations like this.
 *  @TODO: Detailed documentation is needed
 */

namespace ModelUtils;

use Crisu83\ShortId\ShortId;

class ModelUtils
{
    static protected $field_attributes = [
        '_type' => null,
        '_input_type' => null,
        '_min_length' => null,
        '_max_length' => null,
        '_in_options' => null,
        '_input_format' => null,
        '_required' => null,
        '_index' => null,
        '_ref' => null,
        '_has_many' => null,
        '_index_type' => null
    ];
    
    /**
     * Validate given documents
     *
     * @param array     $my_model
     * @param array     $my_doc
     * @param string    $my_key
     * @return array
     * @throws \Exception
     */
    public static function validateDoc($my_model, $my_doc, $my_key = null)
    {
        $my_keys = array_keys($my_doc);
        foreach ($my_keys as $key) {
            
            $my_doc_key_type = self::getType($my_doc[$key]);
            $v_key = $key;
            if ($my_key !== null) {
                $v_key = strval($my_key).".".strval($key);
            }
            // Does doc has a array that does not exist in model definition.
            if (!isset($my_model[$key])) {
                throw new \Exception("Error for key '".$v_key."' that does not exist in the model");
            } // Is the value of the array[key] again another array? .
            elseif ($my_doc_key_type == "array") {
                // Validate this array too.
                $my_doc[$key] = self::validateDoc($my_model[$key], $my_doc[$key], $v_key);
                if (self::getType($my_doc[$key]) != "array") {
                    return $my_doc[$key];
                }
            } // Is the value of the array[key] have same variable type
              //that stated in the definition of the model array.
            elseif ($my_doc_key_type != $my_model[$key]['_type']) {
                throw new \Exception("Error for key '".$v_key."'".", ".$my_doc_key_type.
                    " given but it must be ".$my_model[$key]['_type']);
            } else {
                $my_doc[$key] = self::validateDocItem($my_doc[$key], $my_model[$key], $v_key);
            }
        }
        return $my_doc;
    }
    
    /**
     * @param mixed     $value
     * @param array     $my_model
     * @param string    $key
     *
     * @return mixed
     * @throws \Exception
     */
    private static function validateDocItem($value, $my_model, $key)
    {
        $my_model = self::setDefaultModelAttributes($my_model);
        if (self::getType($value) != $my_model['_type']) {
            return false;
        }
        if ($my_model['_input_type'] !== null) {
            self::filterValidate($my_model['_input_type'], $key, $value, $my_model['_input_format']);
        }
        self::checkMinMaxInOptions($my_model['_type'], $key, $value, $my_model['_min_length'], $my_model['_max_length'], $my_model['_in_options']);
        return $value;
    }
    
    private static function checkMinMaxInOptions($type, $key, $value, $min_length, $max_length, $in_options)
    {
        switch ($type) {
            case 'integer':
            case 'float':
                if ($min_length !== null && ($value<$min_length)) {
                    throw new \Exception("Error for value '".$value."' for '".$key."' couldn't pass the ".
                        "validation: Must be bigger than ".$min_length."  ");
                     
                }
                if ($max_length !== null && ($value>$max_length)) {
                    throw new \Exception("Error for value '".$value."' for '".$key."' couldn't pass the ".
                        "validation: Must be smallerr than ".$max_length."  ");
                }
                break;
            default:
                if ($max_length !== null && (strlen($value)>$max_length)) {
                    throw new \Exception("Error for value '".$value."' for '".$key."' couldn't pass the ".
                        "validation: It's length must be smaller than ".$max_length."  ");
                }
                if ($min_length !== null && (strlen($value)<$min_length)) {
                    throw new \Exception("Error for value '".$value."' for '".$key."' couldn't pass the ".
                        "validation: It's length must be longer than ".$min_length."  ");
                }
                break;
        }
        if ($in_options !== null && (!in_array($value, $in_options))) {
            throw new \Exception("Error for value '".$value."' for '".$key."' couldn't pass the validation: ".
                "It's length must be one of the these values: ".implode(", ", $in_options)."  ");
        }
    }
    
    private static function filterValidate($input_type, $key, $value, $format)
    {
        $filter_check = null;
        $validation = null;
        switch ($input_type) {
            case 'mail':
                $filter_check = filter_var($value, FILTER_VALIDATE_EMAIL);
                $validation = 'INVALID_EMAIL_ADDRESS';
    
                break;
            case 'bool':
                $filter_check = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                $validation = 'INVALID_BOOLEAN_VALUE';
                break;
            case 'url':
                $filter_check = filter_var($value, FILTER_VALIDATE_URL);
                $validation = 'INVALID_URL';
                break;
            case 'ip':
                $filter_check = filter_var($value, FILTER_VALIDATE_IP);
                $validation = 'INVALID_IP_ADDRESS';
                break;
            case 'mac_address':
                $filter_check = filter_var($value, FILTER_VALIDATE_MAC);
                $validation = 'INVALID_MAC_ADDRESS';
                break;
            case 'date':
                $regex = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/";
                $options = array("options"=>array("regexp"=> $regex));
                $filter_check = filter_var($value, FILTER_VALIDATE_REGEXP, $options);
                $validation = 'INVALID_DATE_FORMAT';
                break;
            case 'time':
                $regex = "/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])$/";
                $options = array("options"=>array("regexp"=> $regex));
                $filter_check = filter_var($value, FILTER_VALIDATE_REGEXP, $options);
                $validation = 'INVALID_TIME_FORMAT';
                break;
            case 'datetime':
                $date_part = "[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])";
                $time_part = "([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])";
                $regex = "/^".$date_part." ".$time_part."$/";
                $options = array("options"=>array("regexp"=> $regex));
                $filter_check = filter_var($value, FILTER_VALIDATE_REGEXP, $options);
                $validation = 'INVALID_DATETIME_FORMAT';
                break;
            case 'regex':
                $regex = "/^".$format."$/";
                $options = array("options"=>array("regexp"=> $regex));
                $filter_check = filter_var($value, FILTER_VALIDATE_REGEXP, $options);
                $validation = 'INVALID_FORMAT';
                break;
        }
        if ($filter_check === false) {
            throw new \Exception("Error for value '".$value."' for '".$key."' couldn't pass the ".
                "validation: ".$validation);
        }
        return $filter_check;
    }
    
    /**
     * Fit document to given Model
     *
     * @param array     $my_model
     * @param array     $my_doc
     * @return array
     */
    public static function fitDocToModel($my_model, $my_doc)
    {
        $my_keys = array_keys($my_doc);
        foreach ($my_keys as $key) {
            // If array has a key that is not presented in the model definition, unset it .
            if (!isset($my_model[$key])) {
                unset($my_doc[$key]);
            } // If array[$key] is again an array, recursively fit this array too .
            elseif (self::getType($my_doc[$key]) == "array" && !isset($my_model[$key]['_type'])) {
                $my_doc[$key] = self::fitDocToModel($my_model[$key], $my_doc[$key]);
                // If returned value is not an array, return it .
                if (self::getType($my_doc[$key]) != "array") {
                    return $my_doc[$key];
                }
            } elseif (self::getType($my_doc[$key]) == "array" && $my_model[$key]['_type'] != "array") {
                $my_doc[$key] = $my_model[$key]['_default'];
            } // If array[key] is not an array and not has same variable type that stated in the model definition .
            else {
                $my_doc[$key] = self::sanitizeDocItem($my_doc[$key], $my_model[$key]);
            }
        }

        return $my_doc;
    }

    /**
     * @param array     $my_model
     * @param array     $my_doc
     *
     * @return array
     */
    public static function setModelDefaults($my_model, $my_doc)
    {
        $my_keys = array_keys($my_model);
        $new_doc = [];
        foreach ($my_keys as $key) {
            $item_keys = array_keys($my_model[$key]);
            // If one of the keys of $my_model[$key] is _type this is a definition, not a defined key
            if (in_array("_type", $item_keys)) {
                // If array does not have this key, set the default value .
                if (!isset($my_doc[$key])) {
                    if (isset($my_model[$key]['_input_type'])) {
                        switch ($my_model[$key]['_input_type']) {
                            case 'uid':
                                    $shortid = ShortId::create();
                                    $new_doc[$key] = $shortid->generate();
                                break;
                            case 'date':
                                if ($my_model[$key]['_default'] == 'today') {
                                    $new_doc[$key] = date("Y-m-d");
                                } else {
                                    $new_doc[$key] = $my_model[$key]['_default'];
                                }
                                break;
                            case 'timestamp':
                                $model_default = $my_model[$key]['_default'];
                                $model_type = $my_model[$key]['_type'];
                                if (($model_default == "now") && ($model_type == "integer")) {
                                    $new_doc[$key] = time();
                                } elseif ($model_default == "now" && ($model_type == "string")) {
                                    $new_doc[$key] = date("Y-m-d H:i:s");
                                } else {
                                    $new_doc[$key] = $model_default;
                                }
                                break;
                    
                            default:
                                $new_doc[$key] = $my_model[$key]['_default'];
                        }
                    } else {
                        $new_doc[$key] = $my_model[$key]['_default'];
                    }
                } // If array has this key
                else {
                    // If model definition stated this key's default value is not Null
                    // and has a wrong variable type, fix it.
                    if ($my_model[$key]['_default'] !== null) {
                        $key_type = self::getType($my_doc[$key]);
                        if ($key_type != $my_model[$key]['_type'] && $key_type == "array") {
                            $my_doc[$key] = $my_model[$key]['_default'];
                        }
                        settype($my_doc[$key], $my_model[$key]['_type']);
                    }
                    $new_doc[$key] = $my_doc[$key];
                }
                $new_doc[$key] = self::sanitizeDocItem($new_doc[$key], $my_model[$key]);
            } // If one of the keys is not _type, this is a defined key, recursively get sub keys .
            else {
                if (!isset($my_doc[$key])) {
                    $my_doc[$key] = "";
                }
                $new_doc[$key] = self::setModelDefaults($my_model[$key], $my_doc[$key]);
            }
        }
        return $new_doc;
    }

    private static function setDefaultModelAttributes($my_model){
        
        return array_merge(static::$field_attributes, $my_model);
    }
    
    /**
     * @param mixed     $value
     * @param array     $my_model
     *
     * @return mixed
     */
    private static function sanitizeDocItem($value, $my_model)
    {
        $my_model = self::setDefaultModelAttributes($my_model);
        $value = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (($my_model['_input_type'] == 'timestamp') && ($value == 'now')) {
            $value = time();
        }
        settype($value, $my_model['_type']);
        $value = self::setMaxMinInOptions($my_model['_type'], $value, $my_model['_min_length'], $my_model['_max_length'], $my_model['_in_options']);
        return $value;
    }
    
    private static function setMaxMinInOptions($type, $value, $min_length, $max_length, $in_options)
    {
        switch ($type) {
            case 'integer':
            case 'float':
                if ($min_length !== null && ($value<$min_length)) {
                    $value = $min_length;
                }
                if ($max_length !== null && ($value>$max_length)) {
                    $value = $max_length;
                }
                break;
            case 'string':
                if ($max_length !== null && strlen($value)>$max_length) {
                    $value = substr($value, 0, $max_length);
                }
                break;

        }
        if ($in_options !== null && (!in_array($value, $in_options))) {
            $value = $in_options[0]; // First value of the in_options array is assumed to be the default value .
        }
        return $value;
    }
    /**
     * A Note:
     * Since the built-in php function gettype returns "double" variabe type, here is the workaround function
     * See http://php . net/manual/en/function . gettype . php => Possible values for the returned string are:
     * "double" (for historical reasons "double" is returned in case of a float, and not simply "float")
     *
     * @param mixed     $value
     * @return string
     */
    private static function getType($value)
    {
        return [
            'boolean' => 'boolean',
            'string' => 'string',
            'integer' => 'integer',
            'long' => 'integer',
            'double' => 'float',
            'float' => 'float',
            'array' => 'array',
            'object' => 'object',
            'resource' => 'resource',
			'null' => 'null'
        ][strtolower(gettype($value))];
    }
}
