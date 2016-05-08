<?php
/**
 * ModelUtils: A simple PHP class for validating variable types, fixing, sanitising and setting default values for a
 * model definition encoded as an array . 
 * *
 *  @TODO: A doc item can be array that has multiple values, implement validation and sanitization for the situations like this . 
 *  @TODO: Detailed documentation is needed
 */

namespace ModelUtils;

use Crisu83\ShortId\ShortId;

class ModelUtils
{
    /**
     * Validate given documents
     *
     * @param array     $my_model
     * @param array     $my_doc
     * @param string    $my_key
     * @return array
     * @throws \Exception
     */
    public static function validate_doc($my_model, $my_doc, $my_key = null)
    {
        $my_keys = array_keys($my_doc);
        foreach ($my_keys as $key) {
            // Does doc has a array that does not exist in model definition . 
            if (!isset($my_model[$key])) {
                if ($my_key !== null) {
                    $my_key = strval($my_key) . " . " . strval($key);
                } else {
                    $my_key = $key;
                }
                throw new \Exception("Error for key '" . $my_key . "' that does not exist in the model");
            }
            // Is the value of the array[key] again another array? . 
            elseif (ModelUtils::gettype($my_doc[$key]) == "array") {
                if ($my_key !== null) {
                    $my_key = strval($my_key) . " . " . strval($key);
                } else {
                    $my_key = $key;
                }
                // Validate this array too . 
                $my_doc[$key] = ModelUtils::validate_doc($my_model[$key], $my_doc[$key], $my_key);
                if (ModelUtils::gettype($my_doc[$key]) != "array") {
                    return $my_doc[$key];
                }
            }
            // Does the value of the array[key] have same variable type that stated in the definition of the model array . 
            elseif (ModelUtils::gettype($my_doc[$key]) != $my_model[$key]['_type']) {
                if ($my_key !== null) {
                    $my_key = $my_key . " . " . $key;
                } else {
                    $my_key = $key;
                }
                throw new \Exception("Error for key '" . $my_key . "'" . ", " . ModelUtils::gettype($my_doc[$key]) . 
                    " given but it must be " . $my_model[$key]['_type']);
            } else {
                $v_key = $key;
                if ($my_key !== null) {
                    $v_key = $my_key . " . " . $key;
                }
                $my_doc[$key] = ModelUtils::validate_doc_item($my_doc[$key], $my_model[$key], $v_key);
            }
        }
        return $my_doc;
    }

    /**
     * Fit document to given Model
     *
     * @param array     $my_model
     * @param array     $my_doc
     * @return array
     */
    public static function fit_doc_to_model($my_model, $my_doc)
    {
        $my_keys = array_keys($my_doc);
        foreach ($my_keys as $key) {
            // If array has a key that is not presented in the model definition, unset it . 
            if (! isset($my_model[$key])) {
                unset($my_doc[$key]);
            }
            // If array[$key] is again an array, recursively fit this array too . 
            elseif (ModelUtils::gettype($my_doc[$key]) == "array" && !isset($my_model[$key]['_type'])) {
                $my_doc[$key] = ModelUtils::fit_doc_to_model($my_model[$key], $my_doc[$key]);
                // If returned value is not an array, return it . 
                if (ModelUtils::gettype($my_doc[$key]) != "array") {
                    return $my_doc[$key];
                }
            } elseif (ModelUtils::gettype($my_doc[$key]) == "array" && $my_model[$key]['_type'] != "array") {
                $my_doc[$key]=$my_model[$key]['_default'];
            }
            // If array[key] is not an array and not has same variable type that stated in the model definition . 
            else {
                $my_doc[$key] = ModelUtils::sanitize_doc_item($my_doc[$key], $my_model[$key]);
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
    public static function setting_model_defaults($my_model, $my_doc)
    {
        $my_keys = array_keys($my_model);
        $new_doc =[ ];
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
                                if (($my_model[$key]['_default'] == "now") && ($my_model[$key]['_type'] == "integer")) {
                                    $new_doc[$key] = time();
                                } else if ($my_model[$key]['_default'] == "now" && ($my_model[$key]['_type'] == "string")) {
                                    
                                    $new_doc[$key] = date("Y-m-d H:i:s");
                                } else {
                                    $new_doc[$key] = $my_model[$key]['_default'];
                                }
                                break;
                    
                            default:
                                $new_doc[$key] = $my_model[$key]['_default'];
                        }
                    } else {
                        $new_doc[$key] = $my_model[$key]['_default'];
                    }
                }                
                // If array has this key
                else {
                    // If model definition stated this key's default value is not Null and has a wrong variable type, fix it . 
                    if ($my_model[$key]['_default'] !== null) {
                        if (ModelUtils::gettype($my_doc[$key]) != $my_model[$key]['_type'] && ModelUtils::gettype($my_doc[$key]) == "array") {
                            $my_doc[$key] = $my_model[$key]['_default'];
                        }
                        settype($my_doc[$key], $my_model[$key]['_type']);
                    }
                    $new_doc[$key] = $my_doc[$key];
                }
                $new_doc[$key] = ModelUtils::sanitize_doc_item($new_doc[$key], $my_model[$key]);
            }            
            // If one of the keys is not _type, this is a defined key, recursively get sub keys . 
            else {
                if (!isset($my_doc[$key])) {
                    $my_doc[$key] = "";
                }
                $new_doc[$key] = ModelUtils::setting_model_defaults($my_model[$key], $my_doc[$key]);
            }
        }
        return $new_doc;
    }

    /**
     * @param mixed     $value
     * @param array     $my_model
     * @param string    $key
     *
     * @return mixed
     * @throws \Exception
     */
    public static function validate_doc_item($value, $my_model, $key)
    {
        $type        = isset($my_model['_type']) ? $my_model['_type'] : 'string';
        $input_type  = isset($my_model['_input_type']) ? $my_model['_input_type'] : 'string';
        $format      = isset($my_model['_input_format']) ? $my_model['_input_format'] : "";
        $min_length  = isset($my_model['_min_length']) ? $my_model['_min_length'] : null;
        $max_length  = isset($my_model['_max_length']) ? $my_model['_max_length'] : null;
        $in_options  = isset($my_model['_in_options']) ? $my_model['_in_options'] : null;

        if (ModelUtils::gettype($value) != $type) {
            return false;
        }
        if ($input_type !== null) {
            switch ($input_type) {
                case 'mail':
                    $filter_check = filter_var($value, FILTER_VALIDATE_EMAIL);
                    if ($filter_check === false) {
                        throw new \Exception("Error for value '" . $value . "' for '" . $key . "' couldn't pass the " . 
                            "validation: INVALID_EMAIL_ADDRESS ");
                    }
                    break;
                case 'bool':
                    $filter_check = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    if ($filter_check === false) {
                        throw new \Exception("Error for value '" . $value . "' for '" . $key . "' couldn't pass the " . 
                            "validation: INVALID_BOOLEAN_VALUE ");
                    }
                    break;
                case 'url':
                    $filter_check = filter_var($value, FILTER_VALIDATE_URL);
                    if ($filter_check === false) {
                        throw new \Exception("Error for value '" . $value . "' for '" . $key . "' couldn't pass the " . 
                            "validation: INVALID_URL ");
                    }
                    break;
                case 'ip':
                    $filter_check = filter_var($value, FILTER_VALIDATE_IP);
                    if ($filter_check === false) {
                        throw new \Exception("Error for value '" . $value . "' for '" . $key . "' couldn't pass the " . 
                            "validation: INVALID_IP_ADDRESS ");
                    }
                    break;
                case 'mac_address':
                    $filter_check = filter_var($value, FILTER_VALIDATE_MAC);
                    if ($filter_check === false) {
                        throw new \Exception("Error for value '" . $value . "' for '" . $key . "' couldn't pass the " . 
                            "validation: INVALID_MAC_ADDRESS ");
                    }
                    break;
                case 'date':
                    $regex = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/";
                    $filter_check = filter_var($value, FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=> $regex)));
                    if ($filter_check === false) {
                        throw new \Exception("Error for value '" . $value . "' for '" . $key . "' couldn't pass the " . 
                            "validation: INVALID_FORMAT ");
                    }
                    break;
                case 'time':
                    $regex = "/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])$/";
                    $filter_check = filter_var($value, FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=> $regex)));
                    if ($filter_check === false) {
                        throw new \Exception("Error for value '" . $value . "' for '" . $key . "' couldn't pass the " . 
                            "validation: INVALID_FORMAT ");
                    }
                    break;
                case 'datetime':
                    $regex = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) ([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])$/";
                    $filter_check = filter_var($value, FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=> $regex)));
                    if ($filter_check === false) {
                        throw new \Exception("Error for value '" . $value . "' for '" . $key . "' couldn't pass the validation: INVALID_FORMAT ");
                    }
                    break;
                case 'regex':
                    $regex = "/^".$format."$/";
                    $filter_check = filter_var($value, FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=> $regex)));
                    if ($filter_check === false) {
                        throw new \Exception("Error for value '" . $value . "' for '" . $key . "' couldn't pass the " . 
                            "validation: INVALID_FORMAT ");
                    }
                    break;
            }
        }
        switch ($type) {
            case 'integer':
            case 'float':
                if ($min_length !== null) {
                    if ($value < $min_length) {
                        throw new \Exception("Error for value '" . $value . "' for '" . $key . "' couldn't pass the " . 
                            "validation: Must be bigger than " . $min_length . "  ");
                    }
                }
                if ($max_length !== null) {
                    if ($value>$max_length) {
                        throw new \Exception("Error for value '" . $value . "' for '" . $key . "' couldn't pass the " . 
                            "validation: Must be smallerr than " . $max_length . "  ");
                    }
                }
                break;
            default:
                if ($max_length !== null) {
                    if (strlen($value)>$max_length) {
                        throw new \Exception("Error for value '" . $value . "' for '" . $key . "' couldn't pass the " . 
                            "validation: It's length must be smaller than " . $max_length . "  ");
                    }
                }
                if ($min_length !== null) {
                    if (strlen($value)<$min_length) {
                        throw new \Exception("Error for value '" . $value . "' for '" . $key . "' couldn't pass the " . 
                            "validation: It's length must be longer than " . $min_length . "  ");
                    }
                }
                break;
        }
        if ($in_options !== null) {
            if (!in_array($value, $in_options)) {
                throw new \Exception("Error for value '" . $value . "' for '" . $key . "' couldn't pass the validation: " . 
                    "It's length must be one of the these values: " . implode(", ", $in_options) . "  ");
            }
        }

        return $value;
    }

    /**
     * @param mixed     $value
     * @param array     $my_model
     *
     * @return mixed
     */
    public static function sanitize_doc_item($value, $my_model)
    {
        $type = isset($my_model['_type']) ? $my_model['_type'] : 'string';
        $input_type = isset($my_model['_input_type']) ? $my_model['_input_type'] : null;
        $min_length = isset($my_model['_min_length']) ? $my_model['_min_length'] : null;
        $max_length = isset($my_model['_max_length']) ? $my_model['_max_length'] : null;
        $in_options = isset($my_model['_in_options']) ? $my_model['_in_options'] : null;

        if ($input_type !== null) {
            switch ($input_type) {
                case 'timestamp':
                    if ($value == 'now') {
                        $value = time();
                    }
                    break;
                default:
                    $value = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    break;
            }
        }
        settype($value, $type);
        switch ($type) {
            case 'integer':
            case 'float':
                if ($min_length !== null) {
                    if ($value<$min_length) {
                        $value = $min_length;
                    }
                }
                if ($max_length !== null) {
                    if ($value>$max_length) {
                        $value = $max_length;
                    }
                }
                break;
            default:
                if ($max_length !== null) {
                    if (strlen($value)>$max_length) {
                        $value = substr($value, 0, $max_length);
                    }
                }
                break;
        }
        if ($in_options !== null) {
            if (!in_array($value, $in_options)) {
                $value = $in_options[0]; // First value of the in_options array is assumed to be the default value . 
            }
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
    public static function gettype($value)
    {
        if (is_bool($value)) {
            return "boolean";
        } elseif (is_string($value)) {
            return "string";
        } elseif (is_int($value)) {
            return "integer";
        } elseif (is_float($value)) {
            return "float";
        } elseif (is_array($value)) {
            return "array";
        } elseif (is_null($value)) {
            return "null";
        } elseif (is_object($value)) {
            return "object";
        } elseif (is_resource($value)) {
            return "resource";
        } else {
            return "NA";
        }
    }
}
