<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use ArrayIterator;
use IteratorAggregate;
use yii\base\Object;

/**
 * ConfigManagerObject represents a section within [[ConfigManager]]. It provides methods for retrieving
 * config information from this section.
 *
 * A value in this object can be accessed like it was an object property. If the property doesn't exist
 * an exception is thrown. If you want to avoid an exception being thrown use [[get()]] instead.
 *
 * @property array $data [[ManagerObject]]
 * @property string $id
 * @property string $value
 * @property string $section
 */
class ConfigManagerObject extends ManagerObject implements IteratorAggregate
{
    /**
     * @var string $section the section this config object refers to.
     */
    public $section;
    /**
     * @var ConfigManager $_manager the [[ConfigManager]] that instantiated this object.
     */
    private $_manager;


    /**
     * Constructor
     *
     * @param array $data the data of this object
     * @param ConfigManager $manager the config manager that instatiated this object.
     * @param array $config configuration for this object.
     */
    public function __construct($data, ConfigManager $manager, $config = [])
    {
        $this->_manager = $manager;
        parent::__construct($data, $config);
    }

    /**
     * Returns a config value from this section.
     * By using this method you will receive a default value if the specified name is not set in the config
     * for this section. If you retrieve a config value as a property ($config->property) an exception is thrown 
     * when the name is not set. 
     *
     * @param string $name the name of a config entry.
     * @param mixed $defaultValue a default value returned if $name could not be found in this config object.
     * @return mixed the config value.
     */
    public function get($name, $defaultValue = null)
    {
        return isset($this->data[$name]) ? $this->$name : $defaultValue;
    }

    /**
     * Sets a config value in this config object. The value is saved in the database.
     * Note that the [[ConfigManager]] will update this object if the database save was successful.
     *
     * @param string $name name of the config entry.
     * @param mixed $value value of the config entry.
     * @return bool true if value was saved, false if not.
     */
    public function set($name, $value)
    {
        return $this->_manager->set($name, $value, $this->section);
    }

    /**
     * Sets a value in this config object. The value is NOT saved to the database.
     *
     * @param string $name name of the config entry.
     * @param mixed $value value of the config entry.
     */
    public function setValue($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Removes a value from this config object. This is removed from the database.
     * Note that the [[ConfigManager]] will update this object if the database deletion was successful.
     *
     * @param string $name a name of the value to remove.
     * @return bool true if value was removed, false if not.
     */
    public function remove($name)
    {
        return $this->_manager->remove($name, $this->section);
    }

    /**
     * Removes the specified config name from this config object. The value is NOT removed from the database.
     *
     * @param string $name name of a config entry.
     */
    public function removeValue($name)
    {
         if (isset($this->data[$name])) {
            unset($this->data[$name]);
         }
    }

    /**
     * Returns this config as an zero-index array. Each array element has the entries 'id', 'value' and 'section'.
     * The returned array can be used with [[yii\data\ArrayDataProvider]].
     *
     * @return array this config object as an zero-indexed array.
     */
    public function asArray()
    {
        $data = [];
        foreach ($this->data as $key => $value) {
            $data[] = [
                'id' => $key,
                'value' => $value,
                'section' => $this->section,
            ];
        }
        return $data;
    }

    /**
     * Returns an iterator for traversing the attributes in the config.
     * This method is required by the interface [[\IteratorAggregate]].
     * @return ArrayIterator an iterator for traversing the items in the list.
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }
}
