<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Object;
use yii\caching\Cache;
use yii\db\Query;
use yii\di\Instance;

/**
 * ConfigManager
 *
 * You should use the config manager like so
 * ~~~php
 * $config = Yii::$app->big->configManager->getItems('SECTION NAME / MODULE ID');
 * $adminEmail = $config->get('adminEmail');
 * $isNull = $config->get('value_does_not_exist');
 * 
 * 
 * ~~~
 */
class ConfigManager extends Object
{
    /**
     * @var string name of the table for saving config information.
     */
    public $modelClass = 'bigbrush\big\models\Config';
    /**
     * @var string represents the class when creating an configuration object for a section.
     */
    public $itemClass = 'bigbrush\big\core\ConfigManagerObject';
    /**
     * @var array $_items cache of [[ConfigManagerObject]] classes. The key is a section name
     * and the values are [[ConfigManagerObject]] objects.
     */
    private $_items;


    /**
     * Returns the config value for the provided name.
     * The specified name can be a mapped name separated by a dot. For example:
     * ~~~php
     * $manager->get('adminEmail');
     * ~~~
     * Will return the "adminEmail" value.
     *
     * While a mapped name like this:
     * ~~~php
     * $manager->get('cms.name');
     * ~~~
     * Will return the "name" value from the "cms" section.
     *
     * If a mapped name contains more than 1 dot only the string before the first dotted is relevant for this manager.
     * For instance:
     * ~~~php
     * $manager->get('cms.production.email');
     * ~~~
     * Will return the "production.email" value from the "cms" section.
     *
     * When the provided name is not mapped, only unmapped config entries will be evaluated. When
     * the provided name is mapped, only the provided section will be evaluated.
     *
     * @param string $name the name of a config entry.
     * @param mixed $defaultValue a default value returned if $name could not be found in this config manager.
     * @return mixed the config value.
     */
    public function get($name, $defaultValue = null)
    {
        # code...
    }

    /**
     * Sets a config value in this manager.
     * 
     * @param mixed $name name of a config value to set.
     * @param mixed $value the value to set.
     * @param string $section a section to register the config to.
     * @return mixed the registered value.
     */
    public function set($name, $value, $section)
    {
        # code...
    }

    /**
     * Returns all config items for the specified section. If section is not provided
     * then config for every section is returned. 
     * 
     * @param string $section an optional section to grab config items from.
     * @return array list of config items.
     */
    public function getItems($section = null)
    {
        if (isset($this->_items[$section])) {
            return $this->_items[$section];
        }

        $config = $this->createObject($this->load($section));
        $config->section = $section;
        return $this->_items[$section] = $config;
    }

    /**
     * Saves the provided data to the database. The data must contain the keys "id", "value" and "section".
     * If a record already exist with specified "id" and "section" it will be updated. Otherwise a new record
     * is saved in the database.
     * The provided value could come from Yii::$app->getRequst()->post();
     *
     * @param array $data the data to save. 
     * @return true if data was saved, false if not.
     */
    public function save($data)
    {
        if ($this->isDataValid($data)) {
            $model = $this->getModel();
            $dbModel = $model->find()->where([
                'id' => $data['Config']['id'],
                'section' => $data['Config']['section']
            ])->one();
            if ($dbModel) {
                $model = $dbModel;
            }
            $model->setAttributes($data['Config']);
            return $model->save();
        }
        return false;

    }

    /**
     * Deletes the specified value from the database.
     *
     * @param
     * @param
     * @return true if value was deleted, false if not.
     */
    public function delete($data)
    {
        if ($this->isDataValid($data)) {
            $model = $this->getModel()->find()->where([
                'id' => $data['Config']['id'],
                'section' => $data['Config']['section']
            ])->one();
            if ($model) {
                return $model->delete();
            }
        }
        return false;
    }

    /**
     * 
     *
     * @param array $data the to be saved in the database. Must contain the keys "id", "value" and "section".
     * @return true if data was saved, false if not.
     */
    public function isDataValid($data)
    {
        if (!isset($data['Config']) || !is_array($data['Config'])) {
            return false;
        }
        $data = $data['Config'];
        if (!isset($data['id'], $data['value'], $data['section'])) {
            return false;
        }
        return true;
    }

    /**
     * Configures the provided [[yii\base\Module]] with configurations stored in this manager.
     *
     * @param yii\base\Module $module a module, or a subclass, to configure.
     */
    public function configure($module)
    {
        # code...
    }

    /**
     * Return a query ready to query the database used with this manager.
     *
     * @return Query a query object.
     */
    public function find()
    {
        $query = new Query();
        return $query->from($this->getModel()->tableName());
    }

    /**
     * Creates an item object used in this manager.
     *
     * @param mixed $data configuration array for the object.
     * @return ManagerObject a manager object.
     */
    public function createObject(array $data)
    {
        return Yii::createObject([
            'class' => $this->itemClass
        ], [$data]);
    }

    /**
     * Loads config by the provided section.
     *
     * @param string $section optional section to load config by.
     */
    public function load($section)
    {
        $query = $this->find();
        if ($section) {
            $query->where(['section' => $section])->orderBy('id');
        }
        $data = [];
        foreach ($query->all() as $row) {
            $data[$row['id']] = $row['value'];
        }
        return $data;
    }

    /**
     * Returns an array of [[modelClass]] models for the specified section.
     *
     * @param string $section a section to load models by.
     * @return array list of models.
     */
    public function getModels($section)
    {
        return $this->getModel()->findAll(['section' => $section]);
    }

    /**
     * Returns a model used in this manager.
     * If id is provided the model will be loaded from the database.
     *
     * @param int $id optional id of a database record to load.
     * @return ActiveRecord|null an active record. Null if id is provided but not found.
     */
    public function getModel($id = 0)
    {
        $model = Yii::createObject(['class' => $this->modelClass]);
        if ($id) {
            return $model->findOne($id);
        } else {
            return $model;
        }
    }
}
