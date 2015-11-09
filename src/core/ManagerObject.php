<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use ArrayAccess;
use yii\base\Object;
use yii\base\NotSupportedException;

/**
 * ManagerObject
 */
class ManagerObject extends Object implements ArrayAccess
{
	/**
	 * @var array|object holds object properties.
     * If an object is registered it must implement [[ArrayAccess]].
	 */
    private $_data;

    
    /**
     * Constructor
     *
     * @param array $data the data of this object
     */
    public function __construct($data, $config = [])
    {
    	$this->_data = $data;
        parent::__construct($config);
    }

    /**
     * Returns the value of a property.
     *
     * @param string $name the property name
     * @return mixed the property value
     * @throws UnknownPropertyException if the property is not defined
     */
    public function __get($name)
    {
        if (isset($this->_data[$name])) {
            return $this->_data[$name];
        } else {
            return parent::__get($name);
        }
    }

    /**
     * Sets value of a property.
     *
     * @param string $name the property name or the event name
     * @param mixed $value the property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is read-only
     * @see __get()
     */
    public function __set($name, $value)
    {
        if (isset($this->_data[$name])) {
            $this->_data[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Checks if a property is set, i.e. defined and not null.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `isset($component->property)` or `empty($component->property)`.
     * @param string $name the property name or the event name
     * @return boolean whether the named property is set
     * @see http://php.net/manual/en/function.isset.php
     */
    public function __isset($name)
    {
        if (isset($this->_data[$name])) {
            return true;
        } else {
            return parent::__isset($name);
        }
    }

    /**
     * Returns whether there is an element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `isset($managerObject[$offset])`.
     * @param mixed $offset the offset to check on
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    /**
     * Returns the element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `$value = $managerObject[$offset];`.
     * @param mixed $offset the offset to retrieve element.
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
    }

    /**
     * Not implemented because a manager object is read-only.
     * @param mixed $offset the offset to set element
     * @param mixed $value the element value
     * @throws NotSupportedException
     */
    public function offsetSet($offset, $value)
    {
        if ($offset !== null && isset($this->_data[$offset])) {
            $this->_data[$offset] = $value;
        } else {
            throw new NotSupportedException("ManagerObject properties can only be modified.");
        }
    }

    /**
     * Not implemented because a manager object is read-only.
     * @param mixed $offset the offset to unset element
     * @throws NotSupportedException
     */
    public function offsetUnset($offset)
    {
        throw new NotSupportedException("ManagerObject properties can not be unset.");
    }
}
