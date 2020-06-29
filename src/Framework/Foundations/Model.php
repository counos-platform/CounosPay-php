<?php
/**
 * Created by PhpStorm.
 * User: Mojtaba
 * Date: 12/11/2019
 * Time: 5:38 PM
 */

namespace Counos\CounosPay\Framework\Foundations;

use Counos\CounosPay\Framework\Contracts\Model as ContractModel;

abstract class Model implements ContractModel
{
    protected $attributes = [];
    private   $reflected_properties;

    public function __construct($attributes = [])
    {
        //call __set magic function
        if (is_array((array)$attributes))
        {
            foreach ($attributes as $key => $value)
            {
                $this->{$key} = $value;
            }
        }
    }

    public function __get($key)
    {
        $value = array_key_exists($key, $this->attributes) ? $this->attributes[$key] : null;
        if (method_exists($this, 'get' . $key))
        {
            return $this->{'get' . $key}($value);
        }
        return $value;
    }

    public function __set($key, $value)
    {
        if ($this->reflected_properties === null)
        {
            try
            {
                $reflected = new \ReflectionClass($this);
            }
            catch (\ReflectionException $e)
            {
                throw new \RuntimeException('Cannot Reflect model: ' . $e->getMessage());
            }
            $this->reflected_properties = $reflected->getDocComment();
            $this->reflected_properties = $this->parse($this->reflected_properties);
        }

        if (method_exists($this, 'set' . $key))
        {
            $value = $this->{'set' . $key}($value);
        }
        else
        {
            set_error_handler([$this, 'handel_reflection_warnings'], E_WARNING);
            if (array_key_exists($key, $this->reflected_properties))
            {
                if (class_exists($this->reflected_properties[$key]))
                {
                    $value = new $this->reflected_properties[$key]($value);
                }
                else
                {
                    settype($value, $this->reflected_properties[$key]);
                }
            }
            restore_error_handler();
        }
        $this->attributes[$key] = $value;
    }

    public function __isset($key)
    {
        return array_key_exists($key, $this->reflected_properties);
    }

    public function __toString()
    {
        return (string)json_encode($this->attributes);
    }

    public function toArray()
    {
        $out = [];
        foreach ($this->attributes as $key => $attribute)
        {
            $_out = $attribute;
            if ($attribute instanceof self)
            {
                $_out = $_out->toArray();
            }
            else
            {
                $_out = $this->executeObjectGetFunction($key, $attribute);
            }
            $out[$key] = $_out;
        }
        return $out;
    }

    private function executeObjectGetFunction($key, $value)
    {
        if (method_exists($this, 'get' . $key))
        {
            return $this->{'get' . $key}($value);
        }
        return $value;
    }

    /**
     * Parse a PHP doc comment and extract @property
     *
     * @param string $doc
     * @return array
     */
    protected function parse($doc)
    {
        $notations     = [];
        $raw_notations = $this->extract_notations($doc);
        $raw_notations = $this->join_multiline_notations($raw_notations);
        foreach ($raw_notations as $raw_notation)
        {
            if ($raw_notation['tag'] !== 'property')
            {
                continue;
            }
            $notation                = $this->process_property($raw_notation['value']);
            $notations[$notation[1]] = $notation[0];
        }

        return $notations;
    }

    /**
     * extract type and name of property
     * @param $property
     * @return mixed
     */
    protected function process_property($property)
    {
        $value    = array_chunk(explode(' ', $property), 2);
        $value[0] = str_replace('[]', '', $value[0]);
        return $value[0];
    }

    /**
     * Extract notation from doc comment
     *
     * @param string $doc
     * @return array
     */
    protected function extract_notations($doc)
    {
        $matches     = null;
        $tag         = '\s*@(?<tag>\S+)(?:\h+(?<value>\S.*?)|\h*)';
        $tagContinue = '(?:\040){2}(?<multiline_value>\S.*?)';
        $regex       = '/^\s*(?:(?:\/\*)?\*)?(?:' . $tag . '|' . $tagContinue . ')(?:\*\*\/)?\r?$/m';
        return preg_match_all($regex, $doc, $matches, PREG_SET_ORDER) ? $matches : [];
    }

    /**
     * Join multiline notations
     *
     * @param array $rawNotations
     * @return array
     */
    protected function join_multiline_notations($rawNotations)
    {
        $result        = [];
        $tagsNotations = $this->filter_tags_notations($rawNotations);
        foreach ($tagsNotations as $item)
        {
            if (!empty($item['tag']))
            {
                $result[] = $item;
            }
            else
            {
                $lastIdx                   = count($result) - 1;
                $result[$lastIdx]['value'] = trim($result[$lastIdx]['value'])
                    . ' ' . trim($item['multiline_value']);
            }
        }
        return $result;
    }

    /**
     * Remove everything that goes before tags
     *
     * @param array $rawNotations
     * @return array
     */
    protected function filter_tags_notations($rawNotations)
    {
        $count = count($rawNotations);
        for ($i = 0; $i < $count; $i++)
        {
            if (!empty($rawNotations[$i]['tag']))
            {
                return array_slice($rawNotations, $i);
            }
        }
        return [];
    }

    public function handel_reflection_warnings($error_no, $error_str)
    {
        return !(stripos($error_str, 'failed to open stream: No such file or directory') === false && stripos($error_str, 'Failed opening') === false);
    }

}