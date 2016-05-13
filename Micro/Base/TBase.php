<?php
/**
 * Date: 12-May-16
 * Time: 15:06
 */

namespace Micro\Base;

/**
 * Class TBase
 * @package Micro\Base
 */
trait TBase
{
    /**
     * @param null $className
     * @return mixed
     */
    public static function getInstance($className = null)
    {
        $className = $className === null ? get_called_class() : $className;
        return new $className;
    }
}