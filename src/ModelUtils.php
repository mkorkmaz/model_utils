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
    static protected $fieldAttributes = [
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
     * @param array     $myModel
     * @param array     $myDoc
     * @param string    $myKey
     * @return array
     * @throws \Exception
     */
    public static function validateDoc($myModel, $myDoc, $myKey = null)
    {
        $myKeys = array_keys($myDoc);
        foreach ($myKeys as $key) {
            
            $myDoc_key_type = self::getType($myDoc[$key]);
            $vKey = $key;
            if ($myKey !== null) {
                $vKey = strval($myKey).".".strval($key);
            }
            // Does doc has a array that does not exist in model definition.
            if (!isset($myModel[$key])) {
                throw new \Exception("Error for key '".$vKey."' that does not exist in the model");
            } // Is the value of the array[key] again another array?
            elseif ($myDoc_key_type == "array") {
                // Validate this array too.
                $myDoc[$key] = self::validateDoc($myModel[$key], $myDoc[$key], $vKey);
                if (self::getType($myDoc[$key]) != "array") {
                    return $myDoc[$key];
                }
            } // Is the value of the array[key] have same variable type
                //that stated in the definition of the model array.
            elseif ($myDoc_key_type != $myModel[$key]['_type']) {
                throw new \Exception("Error for key '".$vKey."'".", ".$myDoc_key_type.
                    " given but it must be ".$myModel[$key]['_type']);
            } else {
                $myDoc[$key] = self::validateDocItem($myDoc[$key], $myModel[$key], $vKey);
            }
        }
        return $myDoc;
    }
    
    /**
     * @param mixed     $value
     * @param array     $myModel
     * @param string    $key
     *
     * @return mixed
     * @throws \Exception
     */
    private static function validateDocItem($value, $myModel, $key)
    {
        $myModel = self::setDefaultModelAttributes($myModel);
        if (self::getType($value) != $myModel['_type']) {
            return false;
        }
        if ($myModel['_input_type'] !== null) {
            self::filterValidate($myModel['_input_type'], $key, $value, $myModel['_input_format']);
        }
        self::checkMinMaxInOptions($myModel['_type'], $key, $value, $myModel['_min_length'], $myModel['_max_length'], $myModel['_in_options']);
        return $value;
    }
    
    private static function checkMinMaxInOptions($type, $key, $value, $minLength, $maxLength, $inOptions)
    {
        $error = '';
        switch ($type) {
            case 'integer':
            case 'float':
                if ($minLength !== null && ($value<$minLength)) {
                    $error = "validation: Must be bigger than ".$minLength;
                }
                if ($maxLength !== null && ($value>$maxLength)) {
                    $error = "validation: Must be smallerr than ".$maxLength;
                }
                break;
            default:
                if ($maxLength !== null && (strlen($value)>$maxLength)) {
                    $error = "validation: It's length must be smaller than ".$maxLength;
                }
                if ($minLength !== null && (strlen($value)<$minLength)) {
                    $error = "validation: It's length must be longer than ".$minLength;
                }
                break;
        }
        if ($inOptions !== null && (!in_array($value, $inOptions))) {
            $error = "It's value must be one of the these values: ".implode(", ", $inOptions);
        }
        if ($error != '') {
            throw new \Exception("Error for value '".$value."' for '".$key."' couldn't pass the ".
                "validation: ".$error);
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
     * @param array     $myModel
     * @param array     $myDoc
     * @return array
     */
    public static function fitDocToModel($myModel, $myDoc)
    {
        $myKeys = array_keys($myDoc);
        foreach ($myKeys as $key) {
            // If array has a key that is not presented in the model definition, unset it .
            if (!isset($myModel[$key])) {
                unset($myDoc[$key]);
            } // If array[$key] is again an array, recursively fit this array too .
            elseif (self::getType($myDoc[$key]) == "array" && !isset($myModel[$key]['_type'])) {
                $myDoc[$key] = self::fitDocToModel($myModel[$key], $myDoc[$key]);
                // If returned value is not an array, return it .
                if (self::getType($myDoc[$key]) != "array") {
                    return $myDoc[$key];
                }
            } elseif (self::getType($myDoc[$key]) == "array" && $myModel[$key]['_type'] != "array") {
                $myDoc[$key] = $myModel[$key]['_default'];
            } // If array[key] is not an array and not has same variable type that stated in the model definition .
            else {
                $myDoc[$key] = self::sanitizeDocItem($myDoc[$key], $myModel[$key]);
            }
        }

        return $myDoc;
    }

    /**
     * @param array     $myModel
     * @param array     $myDoc
     *
     * @return array
     */
    public static function setModelDefaults($myModel, $myDoc)
    {
        $myKeys = array_keys($myModel);
        $newDoc = [];
        foreach ($myKeys as $key) {
            $item_keys = array_keys($myModel[$key]);
            // If one of the keys of $myModel[$key] is _type this is a definition, not a defined key
            if (in_array("_type", $item_keys)) {
                // If array does not have this key, set the default value .
                if (!isset($myDoc[$key])) {
                    if (isset($myModel[$key]['_input_type'])) {
                        switch ($myModel[$key]['_input_type']) {
                            case 'uid':
                                    $shortid = ShortId::create();
                                    $newDoc[$key] = $shortid->generate();
                                break;
                            case 'date':
                                if ($myModel[$key]['_default'] == 'today') {
                                    $newDoc[$key] = date("Y-m-d");
                                } else {
                                    $newDoc[$key] = $myModel[$key]['_default'];
                                }
                                break;
                            case 'timestamp':
                                $model_default = $myModel[$key]['_default'];
                                $model_type = $myModel[$key]['_type'];
                                if (($model_default == "now") && ($model_type == "integer")) {
                                    $newDoc[$key] = time();
                                } elseif ($model_default == "now" && ($model_type == "string")) {
                                    $newDoc[$key] = date("Y-m-d H:i:s");
                                } else {
                                    $newDoc[$key] = $model_default;
                                }
                                break;
                    
                            default:
                                $newDoc[$key] = $myModel[$key]['_default'];
                        }
                    } else {
                        $newDoc[$key] = $myModel[$key]['_default'];
                    }
                } // If array has this key
                else {
                    // If model definition stated this key's default value is not Null
                    // and has a wrong variable type, fix it.
                    if ($myModel[$key]['_default'] !== null) {
                        $key_type = self::getType($myDoc[$key]);
                        if ($key_type != $myModel[$key]['_type'] && $key_type == "array") {
                            $myDoc[$key] = $myModel[$key]['_default'];
                        }
                        settype($myDoc[$key], $myModel[$key]['_type']);
                    }
                    $newDoc[$key] = $myDoc[$key];
                }
                $newDoc[$key] = self::sanitizeDocItem($newDoc[$key], $myModel[$key]);
            } // If one of the keys is not _type, this is a defined key, recursively get sub keys .
            else {
                if (!isset($myDoc[$key])) {
                    $myDoc[$key] = "";
                }
                $newDoc[$key] = self::setModelDefaults($myModel[$key], $myDoc[$key]);
            }
        }
        return $newDoc;
    }

    private static function setDefaultModelAttributes($myModel)
    {
        return array_merge(static::$fieldAttributes, $myModel);
    }
    
    /**
     * @param mixed     $value
     * @param array     $myModel
     *
     * @return mixed
     */
    private static function sanitizeDocItem($value, $myModel)
    {
        $myModel = self::setDefaultModelAttributes($myModel);
        $value = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (($myModel['_input_type'] == 'timestamp') && ($value == 'now')) {
            $value = time();
        }
        settype($value, $myModel['_type']);
        $value = self::setMaxMinInOptions($myModel['_type'], $value, $myModel['_min_length'], $myModel['_max_length'], $myModel['_in_options']);
        return $value;
    }
    
    private static function setMaxMinInOptions($type, $value, $minLength, $maxLength, $inOptions)
    {
        switch ($type) {
            case 'integer':
            case 'float':
                if ($minLength !== null && ($value<$minLength)) {
                    $value = $minLength;
                }
                if ($maxLength !== null && ($value>$maxLength)) {
                    $value = $maxLength;
                }
                break;
            case 'string':
                if ($maxLength !== null && strlen($value)>$maxLength) {
                    $value = substr($value, 0, $maxLength);
                }
                break;

        }
        if ($inOptions !== null && (!in_array($value, $inOptions))) {
            $value = $inOptions[0]; // First value of the in_options array is assumed to be the default value .
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
