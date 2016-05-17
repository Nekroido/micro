<?php
/**
 * Date: 12-May-16
 * Time: 15:07
 */

namespace Micro\Base;

/**
 * Class ModelBase
 * @package Micro\Base
 */
abstract class ModelBase
{
    use TBase {
        TBase::getInstance as model;
    }

    /**
     * @var array
     */
    private $dynamicProperties = [];

    /**
     * @param string $propertyName
     * @param mixed $value
     */
    public function __set($propertyName, $value)
    {
        $setterName = 'set' . ucfirst($propertyName);

        if (isset($this->$propertyName))
            $this->$propertyName = $value;
        else if (method_exists($this, $setterName))
            $this->$setterName($value);
        else
            $this->dynamicProperties[$propertyName] = $value;
    }

    /**
     * @param string $propertyName
     * @return mixed
     */
    public function __get($propertyName)
    {
        $getterName = 'get' . ucfirst($propertyName);

        if (property_exists($this, $propertyName)) {
            $reflection = new \ReflectionProperty($this, $propertyName);
            $reflection->setAccessible($propertyName);
            return $reflection->getValue($this);
        } else if (isset($this->dynamicProperties[$propertyName]))
            return $this->dynamicProperties[$propertyName];
        else if (method_exists($this, $getterName))
            return $this->$getterName();
        else {
            $trace = debug_backtrace();
            trigger_error(
                'Undefined property: ' . $propertyName .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line'],
                E_USER_NOTICE);

            return null;
        }
    }

    /**
     * @param string $propertyName
     * @return bool
     */
    public function __isset($propertyName)
    {
        return empty($this->$propertyName)
        || isset($this->dynamicProperties[$propertyName])
        || method_exists($this, 'get' . ucfirst($propertyName));
    }
}