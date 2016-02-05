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
 * $manager = Yii::$app->big->configManager;
 * $config = $manager->getItems('SECTION NAME');
 * 
 * $systemEmail = $config->get('systemEmail'); // will return null if "systemEmail" is not set.
 * or
 * $systemEmail = $config->systemEmail; // will throw exception if "systemEmail" is not set.
 * 
 * $isNull = $config->get('name_does_not_exist', null);
 * 
 * // setting properties
 * $config->set('name', 'value');
 * or
 * $manager->set('name', 'value', 'section');
 * 
 * // the manager has a shorthand method for retrieving config values:
 * $manager->get('section.name', 'defaultValue');
 * $manager->get('cms.systemEmail', 'noreply@noreply.com');
 * ~~~
 * See [[get()]] for more information on the shorthand method.
 */
class ConfigManager extends Object implements ManagerInterface
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
     * @var array $items cache container containing [[ConfigManagerObject]] objects. The keys are section names
     * and the values are [[ConfigManagerObject]] objects.
     * Structure:
     * ~~~php
     * [
     *     'SECTION NAME' => ConfigManagerObject,
     *     'cms' => ConfigManagerObject,
     *     ...
     * ]
     * ~~~
     */
    protected $items;


    /**
     * Returns a config value based on the specified name. This is a shorthand for [[ConfigManagerObject::get()]].
     * The specified name must be a mapped name containing minimum 1 dot. For example:
     * ~~~php
     * $manager->get('cms.systemEmail');
     * ~~~
     * Will return the "systemEmail" value from the "cms" section.
     *
     * If a mapped name contains more than 1 dot only the string before the first dot is relevant.
     * For instance:
     * ~~~php
     * $manager->get('cms.production.systemEmail');
     * ~~~
     * Will return the "production.systemEmail" value from the "cms" section.
     *
     * Note that the specified name MUST be mapped (by 1 or more dots).
     *
     * @param string $name a mapped name of a config entry.
     * @param mixed $defaultValue a default value returned if $name could not be found in this config manager.
     * @return mixed the config value.
     */
    public function get($name, $defaultValue = null)
    {
        if (strpos($name, '.') === false) {
            return $defaultValue;
        }
        list($section, $property) = explode('.', $name, 2);
        return $this->getItems($section)->get($property, $defaultValue);
    }

    /**
     * Saves/updates a config value in the database. If the save is successful the
     * [[ConfigManagerObject]] for the specified section is updated.
     * 
     * @param string $name name of a config value to set.
     * @param string $value the value to set.
     * @param string $section a section to register the config to.
     * @return bool true if value was saved, false if not.
     */
    public function add($name, $value, $section)
    {
        $dataSaved = $this->save([
            'Config' => [
                'id' => $name,
                'value' => $value,
                'section' => $section,
            ]
        ]);
        if ($dataSaved) {
            $this->getItems($section)->setValue($name, $value);
            return true;
        }
        return false;
    }

    /**
     * Returns a config object with configuration for the specified section.
     * 
     * @param string $section a section to grab config items from.
     * @return ConfigManagerObject a config object.
     */
    public function getItems($section = null)
    {
        if (isset($this->items[$section])) {
            return $this->items[$section];
        }

        $data = $this->load($section);
        $config = $this->createObject($data);
        return $this->items[$section] = $config;
    }

    /**
     * Defined in [[ManagerInterface]]. Delegates to [[getItems()]].
     *
     * @param string $section a section to grab config items from.
     * @return ConfigManagerObject a config object.
     */
    public function getItem($section)
    {
        return $this->getItems($section);
    }

    /**
     * Saves the provided data to the database. The data must contain the keys "id", "value" and "section" in
     * the "Config" namespace. For instance:
     * ~~~php
     * $data = [
     *     'Config' => [
     *         'id' => 'REQUIRED',
     *         'value' => 'CAN BE EMPTY',
     *         'section' => 'REQUIRED',
     *     ],
     * ];
     * ~~~
     * If a record already exist with specified "id" and "section" it will be updated with "value. Otherwise
     * a new record is saved in the database.
     * The provided data could come from Yii::$app->getRequst()->post();.
     *
     * @param array $data the data to save. 
     * @return bool true if data was saved, false if not.
     */
    public function save($data)
    {
        if ($this->isDataValid($data)) {
            $data = $data['Config'];
            $model = $this->getModel();
            $dbModel = $model->find()->where([
                'id' => $data['id'],
                'section' => $data['section']
            ])->one();
            if ($dbModel) {
                $model = $dbModel;
            }
            $model->setAttributes($data);
            return $model->save();
        }
        return false;

    }

    /**
     * Deletes the specified data from the database.
     *
     * @param array $data the data to be deleted. The data must contain the keys "id" and "section" in
     * the "Config" namespace. For instance:
     * ~~~php
     * $data = [
     *     'Config' => [
     *         'id' => 'REQUIRED',
     *         'section' => 'REQUIRED',
     *     ],
     * ];
     * @return bool true if value was deleted, false if not.
     */
    public function delete($data)
    {
        if ($this->isDataValid($data)) {
            $data = $data['Config'];
            $model = $this->getModel()->find()->where([
                'id' => $data['id'],
                'section' => $data['section']
            ])->one();
            if ($model) {
                return $model->delete();
            }
        }
        return false;
    }

    /**
     * Returns a boolean indicating whether the provided data array is valid. A valid array has an array set in
     * the "Config" namespace. The array in "Config" must contain the keys "id", "value" and "section".
     * For instance:
     * ~~~php
     * $data = [
     *     'Config' => [
     *         'id' => 'REQUIRED',
     *         'value' => 'REQUIRED',
     *         'section' => 'REQUIRED',
     *     ],
     * ];
     *
     * @param array $data the to be saved in the database.
     * @return true if data was saved, false if not.
     */
    public function isDataValid($data)
    {
        if (!is_array($data['Config']) || !isset($data['Config'])) {
            return false;
        }
        $data = $data['Config'];
        return isset($data['id'], $data['value'], $data['section']);
    }

    /**
     * Configures the provided [[yii\base\Module]] with configurations stored in this manager.
     *
     * @param yii\base\Module $module a module to configure.
     * @param string $section optional section to load config from. If this is not provided
     * the value $module->id is used. Can be used to setup the module with a specific config section.
     * @return yii\base\Module the module with updated configurations.
     */
    public function configureModule($module, $section = null)
    {
        $section = $section ?: $module->id;
        $config = $this->getItems($section);
        foreach ($config as $name => $value) {
            $module->$name = $value;
        }
        return $module;
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
     * The specified data must contain the key "section". Use [[load()]] to ensure properly
     * formatted data.
     *
     * @param mixed $data configuration array for the object.
     * @return ManagerObject a manager object.
     */
    public function createObject(array $data)
    {
        $section = $data['section'];
        unset($data['section']);
        return Yii::createObject([
            'class' => $this->itemClass,
            'section' => $section,
        ], [$data, $this]);
    }

    /**
     * Loads config by the provided section.
     *
     * @param string $section a section to load config by.
     */
    public function load($section)
    {
        $query = $this->find()->select(['id', 'value']);
        if ($section) {
            $query->where(['section' => $section])->orderBy('id');
        }
        $data = ['section' => $section];
        foreach ($query->all() as $row) {
            $data[$row['id']] = $row['value'];
        }
        return $data;
    }

    /**
     * Returns a model used in this manager.
     * A new model is always returned.
     *
     * @param int $id optional id of a database record to load.
     * @return ActiveRecord|null an active record. Null if id is provided but not found.
     */
    public function getModel($id = 0)
    {
        return Yii::createObject(['class' => $this->modelClass]);
    }
}
