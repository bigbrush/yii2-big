<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\BaseObject;
use yii\base\InvalidParamException;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * ConfigManager provides an easy way to implement a key=>value configuration system.
 *
 * You should use the config manager like so:
 *
 * ~~~php
 * $manager = Yii::$app->big->configManager;
 * $config = $manager->getItems('SECTION NAME');
 *
 * $systemEmail = $config->get('systemEmail'); // will return null if "systemEmail" is not set.
 * // or
 * $systemEmail = $config->systemEmail; // will throw exception if "systemEmail" is not set.
 *
 * $isNull = $config->get('name_does_not_exist', null);
 *
 * // setting properties
 * $config->set('name', 'value');
 * // or
 * $manager->set('name', 'value', 'section');
 *
 * // the manager has a shorthand method for retrieving config values:
 * $manager->get('section.name', 'defaultValue');
 * $manager->get('cms.systemEmail', 'noreply@noreply.com');
 * ~~~
 *
 * See [[get()]] for more information on the shorthand method.
 *
 * The ConfigManager can be configured for each section specifically. Either by a custom rule
 * object that implements [[ConfigManagerRuleInterface]] or a configuration array for the default
 * rule object [[ConfigManagerRule]].
 * The ConfigManager can be configured through the application configuration like so:
 *
 * ~~~php
 * ...
 * 'components' => [
 *     'big' => [
 *         'managers' => [
 *               'configManager' => [
 *                   'rules' => [
 *                       'cart' =>[
 *                           'lockedFields' => ['category.products_per_page', 'product.show_prices'],
 *                           'changeLockedFields' => true,
 *                       ],
 *                   ],
 *               ],
 *         ],
 *     ],
 * ]
 * ...
 * ~~~
 *
 * And through code like so:
 *
 * ~~~php
 * Yii::$app->big->configManager->configureSection('cms', [
 *    'lockedFields' => ['appName', 'systemEmail'],
 *    'changeLockedFields' => true,
 * ]);
 *
 * // with a custom rule object
 * Yii::$app->big->configManager->configureSection('cms', new MyConfigManagerRule());
 * ~~~
 *
 */
class ConfigManager extends BaseObject implements ManagerInterface
{
    /**
     * @var string $modelClass a class name of the model used by this manager.
     */
    public $modelClass = 'bigbrush\big\models\Config';
    /**
     * @var ManagerObject $itemClass represents the class when creating a configuration object for a section.
     */
    public $itemClass = 'bigbrush\big\core\ConfigManagerObject';
    /**
     * @var ConfigManagerRule $ruleClass represents the class when creating a rule object for a section.
     */
    public $ruleClass = 'bigbrush\big\core\ConfigManagerRule';
    /**
     * @var array $rules list of objects or configuration arrays indexed by the section each one belongs to.
     *
     * For example:
     *
     * ~~~php
     * [
     *     'SECTION NAME' => ConfigManagerRule,
     *     'cms' => ConfigManagerRule,
     *     'SECTION NAME' => [
     *         'lockedFields' => ['field1'],
     *     ],
     *     ...
     * ]
     * ~~~
     *
     * If an object is registered with [[setRules()]] or [[configureSection]] it must implement [[ConfigManagerRuleInterface]].
     */
    protected $rules = [];
    /**
     * @var array $items cache container containing [[ConfigManagerObject]] objects. The keys are section names
     * and the values are [[ConfigManagerObject]] objects.
     *
     * Structure:
     *
     * ~~~php
     * [
     *     'SECTION NAME' => ConfigManagerObject,
     *     'cms' => ConfigManagerObject,
     *     ...
     * ]
     * ~~~
     *
     */
    protected $items;


    /**
     * Returns a config value based on the specified name. This is a shorthand for [[ConfigManagerObject::get()]].
     * The specified name must be a mapped name containing minimum 1 dot. For example:
     *
     * ~~~php
     * $manager->get('cms.systemEmail');
     * ~~~
     *
     * Will return the "systemEmail" value from the "cms" section.
     *
     * If a mapped name contains more than 1 dot only the string before the first dot is relevant.
     * For instance:
     *
     * ~~~php
     * $manager->get('cms.production.systemEmail');
     * ~~~
     *
     * Will return the "production.systemEmail" value from the "cms" section.
     *
     * Note that the specified name MUST contain at least 1 dot.
     *
     * @param string $name a mapped name of a config entry.
     * @param mixed $defaultValue a default value returned if $name could not be found.
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
     * Returns a config object with configurations for the specified section.
     *
     * @param string $section a section to return config items for.
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
     * Saves/updates a config value in the database. If the save is successful the
     * [[ConfigManagerObject]] for the specified section is updated.
     *
     * @param string $name name of a config value to set.
     * @param string $value the value to set.
     * @param string $section a section to register the config to.
     * @return bool true if value was saved, false if not.
     */
    public function set($name, $value, $section)
    {
        $data = [
            'Config' => [
                'id' => $name,
                'value' => $value,
                'section' => $section,
            ]
        ];
        if ($this->save($data)) {
            $this->getItems($section)->setValue($name, $value);
            return true;
        }
        return false;
    }

    /**
     * Removes the specified config name from the specified section.
     *
     * @param string $name name of a config value to remove.
     * @param string $section the section to remove the config name from.
     * @return bool true if value was removed, false if not.
     */
    public function remove($name, $section)
    {
        $data = [
            'Config' => [
                'id' => $name,
                'value' => '',
                'section' => $section,
            ],
        ];
        if ($this->delete($data)) {
            $this->getItems($section)->removeValue($name);
            return true;
        }
        return false;
    }

    /**
     * Registers the specified config manager rule to the specified section.
     *
     * @param string $section a section to configure.
     * @param array|ConfigManagerRuleInterface $config configuration array for a rule or an rule object.
     */
    public function configureSection($section, $config)
    {
        $this->setRules([$section => $config]);
    }

    /**
     * Registers an array of objects that iplements [[ConfigManagerRuleInterface]].
     *
     * @param array $config an array of [[ConfigManagerRuleInterface]] objects or configuration arrays to create rule objects.
     * The keys are section names and the values can be either:
     *     - a configuration array
     *     - objects that must implement the [[ConfigManagerRuleInterface]] interface.
     *
     * Note that if a rule for a specfied section already exists it will be overridden.
     */
    public function setRules($config)
    {
        foreach ($config as $section => $rules) {
            $this->rules[$section] = $rules;
        }
    }

    /**
     * Returns all rules applied in this config manager.
     *
     * @return array list of applied rules.
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Returns a rule object that implements [[ConfigManagerRuleInterface]] for the specified section.
     *
     * @param string $section a section to return a rule object for.
     * @return ConfigManagerRuleInterface a rule object.
     * @throws InvalidParamException if a registered rule object does not implement [[ConfigManagerRuleInterface]].
     */
    public function getRule($section)
    {
        $rule = isset($this->rules[$section]) ? $this->rules[$section] : [];
        if (is_array($rule)) {
            if (isset($rule['class'])) {
                $class = $rule['class'];
                unset($rule['class']);
            } else {
                $class = $this->ruleClass;
            }
            $rule = Yii::createObject([
                'class' => $class,
                'rules' => $rule,
            ]);
        }
        if (!$rule instanceof ConfigManagerRuleInterface) {
            throw new InvalidParamException("The ConfigManager rule '" . get_class($rule) . "' must implement the 'bigbrush\big\core\ConfigManagerRuleInterface' interface");
        }
        return $this->rules[$section] = $rule;
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
     * Saves the provided data to the database. The data must contain the keys "id", "value" and "section"
     * the "Config" namespace. For instance:
     *
     * ~~~php
     * $data = [
     *     'Config' => [
     *         'id' => 'REQUIRED',
     *         'value' => 'CAN BE EMPTY',
     *         'section' => 'REQUIRED',
     *     ],
     * ];
     * ~~~
     *
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
            $model = $this->getModel([$data['id'], $data['section']]);
            if (!$model) {
                $model = $this->getModel();
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
     *         'value' => 'CAN BE EMPTY',
     *         'section' => 'REQUIRED',
     *     ],
     * ];
     * @return bool true if value was deleted, false if not.
     */
    public function delete($data)
    {
        if ($this->isDataValid($data)) {
            $data = $data['Config'];
            $model = $this->getModel([$data['id'], $data['section']]);
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
     *
     * ~~~php
     * $data = [
     *     'Config' => [
     *         'id' => 'REQUIRED',
     *         'value' => 'CAN BE EMPTY',
     *         'section' => 'REQUIRED',
     *     ],
     * ];
     * ~~~
     *
     * @param array $data the to be saved in the database.
     * @return true if data was saved, false if not.
     */
    public function isDataValid($data)
    {
        if (!isset($data['Config']) || !is_array($data['Config'])) {
            return false;
        }
        $data = $data['Config'];
        return isset($data['id'], $data['value'], $data['section']) && !empty($data['id']) && !empty($data['section']);
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
     * @return yii\db\Query a query object.
     */
    public function find()
    {
        $query = new Query();
        return $query->from($this->getModel()->tableName());
    }

    /**
     * Creates an item object used in this manager.
     * The specified data MUST contain the key "section". Use [[load()]] to ensure properly
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
     * The model will be setup with a [[ConfigManagerRuleInterface]] so each section can be validated
     * separately.
     *
     * @param array $id optional array containing a composite primary key consisting of ['id', 'section'].
     * @return ActiveRecord|null an active record. Null if id is provided but not found.
     */
    public function getModel($id = [])
    {
        $model = Yii::createObject($this->modelClass);
        if (!empty($id)) {
            $model = $model->findOne($id);
        }
        if ($model) {
            $rule = $this->getRule($model->section);
            $model->on(ActiveRecord::EVENT_BEFORE_INSERT, function($event) use ($rule) {
                $event->isValid = $rule->onBeforeSave($event->sender);
                if (!$event->isValid) {
                    $event->sender->addError('id', $rule->getMessage());
                }
            });
            $model->on(ActiveRecord::EVENT_BEFORE_UPDATE, function($event) use ($rule) {
                $event->isValid = $rule->onBeforeSave($event->sender);
                if (!$event->isValid) {
                    $event->sender->addError('id', $rule->getMessage());
                }
            });
            $model->on(ActiveRecord::EVENT_BEFORE_DELETE, function($event) use ($rule) {
                $event->isValid = $rule->onBeforeDelete($event->sender);
                if (!$event->isValid) {
                    $event->sender->addError('id', $rule->getMessage());
                }
            });
        }
        return $model;
    }
}
