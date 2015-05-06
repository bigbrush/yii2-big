<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use yii\base\Object;

/**
 * ManagerObject
 */
class ManagerObject extends Object
{
	/**
	 * @var array holds object properties. 
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
}