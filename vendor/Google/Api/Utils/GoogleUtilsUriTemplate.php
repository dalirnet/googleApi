<?php

namespace Google\Api\Utils;

class GoogleUtilsUriTemplate
{
    const TYPE_MAP = "1";
    const TYPE_LIST = "2";
    const TYPE_SCALAR = "4";
    private $operators = array("+" => "reserved", "/" => "segments", "." => "dotprefix", "#" => "fragment", ";" => "semicolon", "?" => "form", "&" => "continuation");
    private $reserved = array("=", ",", "!", "@", "|", ":", "/", "?", "#", "[", "]", '$', "&", "'", "(", ")", "*", "+", ";");
    private $reservedEncoded = array("%3D", "%2C", "%21", "%40", "%7C", "%3A", "%2F", "%3F", "%23", "%5B", "%5D", "%24", "%26", "%27", "%28", "%29", "%2A", "%2B", "%3B");

    public function parse($string, array $parameters)
    {
        return $this->resolveNextSection($string, $parameters);
    }

    private function resolveNextSection($string, $parameters)
    {
        $start = strpos($string, "{");
        if ($start === false) {
            return $string;
        }
        $end = strpos($string, "}");
        if ($end === false) {
            return $string;
        }
        $string = $this->replace($string, $start, $end, $parameters);
        return $this->resolveNextSection($string, $parameters);
    }

    private function replace($string, $start, $end, $parameters)
    {
        $data = substr($string, $start + 1, $end - $start - 1);
        if (isset($this->operators[$data[0]])) {
            $op = $this->operators[$data[0]];
            $data = substr($data, 1);
            $prefix = "";
            $prefix_on_missing = false;
            switch ($op) {
                case "reserved":
                    $data = $this->replaceVars($data, $parameters, ",", null, true);
                    break;
                case "fragment":
                    $prefix = "#";
                    $prefix_on_missing = true;
                    $data = $this->replaceVars($data, $parameters, ",", null, true);
                    break;
                case "segments":
                    $prefix = "/";
                    $data = $this->replaceVars($data, $parameters, "/");
                    break;
                case "dotprefix":
                    $prefix = ".";
                    $prefix_on_missing = true;
                    $data = $this->replaceVars($data, $parameters, ".");
                    break;
                case "semicolon":
                    $prefix = ";";
                    $data = $this->replaceVars($data, $parameters, ";", "=", false, true, false);
                    break;
                case "form":
                    $prefix = "?";
                    $data = $this->replaceVars($data, $parameters, "&", "=");
                    break;
                case "continuation":
                    $prefix = "&";
                    $data = $this->replaceVars($data, $parameters, "&", "=");
                    break;
            }
            if ($data || ($data !== false && $prefix_on_missing)) {
                $data = $prefix . $data;
            }

        } else {

            $data = $this->replaceVars($data, $parameters);
        }
        return substr($string, 0, $start) . $data . substr($string, $end + 1);
    }

    private function replaceVars($section, $parameters, $sep = ",", $combine = null, $reserved = false, $tag_empty = false, $combine_on_empty = true)
    {
        if (strpos($section, ",") === false) {
            return $this->combine($section, $parameters, $sep, $combine, $reserved, $tag_empty, $combine_on_empty);
        } else {
            $vars = explode(",", $section);
            return $this->combineList($vars, $sep, $parameters, $combine, $reserved, false, $combine_on_empty);
        }
    }

    public function combine($key, $parameters, $sep, $combine, $reserved, $tag_empty, $combine_on_empty)
    {
        $length = false;
        $explode = false;
        $skip_final_combine = false;
        $value = false;
        if (strpos($key, ":") !== false) {
            list($key, $length) = explode(":", $key);
        }
        if ($key[strlen($key) - 1] == "*") {
            $explode = true;
            $key = substr($key, 0, -1);
            $skip_final_combine = true;
        }
        $list_sep = $explode ? $sep : ",";
        if (isset($parameters[$key])) {
            $data_type = $this->getDataType($parameters[$key]);
            switch ($data_type) {
                case self::TYPE_SCALAR:
                    $value = $this->getValue($parameters[$key], $length);
                    break;
                case self::TYPE_LIST:
                    $values = array();
                    foreach ($parameters[$key] as $pkey => $pvalue) {
                        $pvalue = $this->getValue($pvalue, $length);
                        if ($combine && $explode) {
                            $values[$pkey] = $key . $combine . $pvalue;
                        } else {
                            $values[$pkey] = $pvalue;
                        }
                    }
                    $value = implode($list_sep, $values);
                    if ($value == '') {
                        return '';
                    }
                    break;
                case self::TYPE_MAP:
                    $values = array();
                    foreach ($parameters[$key] as $pkey => $pvalue) {
                        $pvalue = $this->getValue($pvalue, $length);
                        if ($explode) {
                            $pkey = $this->getValue($pkey, $length);
                            $values[] = $pkey . "=" . $pvalue;
                        } else {
                            $values[] = $pkey;
                            $values[] = $pvalue;
                        }
                    }
                    $value = implode($list_sep, $values);
                    if ($value == '') {
                        return false;
                    }
                    break;
            }
        } else if ($tag_empty) {

            return $key;
        } else {

            return false;
        }
        if ($reserved) {
            $value = str_replace($this->reservedEncoded, $this->reserved, $value);
        }
        if (!$combine || $skip_final_combine) {
            return $value;
        }
        return $key . ($value != '' || $combine_on_empty ? $combine . $value : '');
    }

    private function getDataType($data)
    {
        if (is_array($data)) {
            reset($data);
            if (key($data) !== 0) {
                return self::TYPE_MAP;
            }
            return self::TYPE_LIST;
        }
        return self::TYPE_SCALAR;
    }

    private function getValue($value, $length)
    {
        if ($length) {
            $value = substr($value, 0, $length);
        }
        $value = rawurlencode($value);
        return $value;
    }

    private function combineList($vars, $sep, $parameters, $combine, $reserved, $tag_empty, $combine_on_empty)
    {
        $ret = array();
        foreach ($vars as $var) {
            $response = $this->combine($var, $parameters, $sep, $combine, $reserved, $tag_empty, $combine_on_empty);
            if ($response === false) {
                continue;
            }
            $ret[] = $response;
        }
        return implode($sep, $ret);
    }
}
