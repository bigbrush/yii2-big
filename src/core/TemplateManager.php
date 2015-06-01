<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\InvalidParamException;
use yii\base\Object;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * TemplateManager
 */
class TemplateManager extends Object
{
    /**
     * @var string the text used for selecting the default template in drop down lists. The value is being translated 
     * in [[init()]].
     */
    public $defaultText = '- Use default template -';
    /**
     * @var string name of a database table to load templates from.
     */
    public $tableName = '{{%template}}';
    /**
     * @var string represents the model class when creating/editing a template.
     * The table is aliased by the letter "t".
     */
    public $modelClass = 'bigbrush\big\models\Template';
    /**
     * @var string represents the class used when creating a template.
     */
    public $itemClass = 'bigbrush\big\core\TemplateManagerObject';
    /**
     * @var TemplateManagerObject the active template.
     */
    private $_active;


    /**
     * Initializes by setting an empty manager object as the current active.
     */
    public function init()
    {
        $this->reset();
    }

    /**
     * Returns all created templates. If the provided value is true arrays of templates are returned.
     *
     * @param boolean $asArray whether to load all templates as arrays.
     * @return array list of [[bigbrush\big\models\Template]] models or arrays of template data.
     */
    public function getTemplates($asArray = true)
    {
        $query = $this->getModel()->find();
        if ($asArray) {
            $query->asArray();
        }
        return $query->all();
    }

    /**
     * Returns an array ready for drop down lists.
     *
     * @param boolean $enableDefault whether to include using the default template.
     * @return array templates ready for a drop down list.
     */
    public function getDropDownList($enableDefault = true)
    {
        $templates = ArrayHelper::map($this->find()->all(), 'id', 'title');
        if ($enableDefault) {
            return [Yii::t('big', $this->defaultText)] + $templates;
        } else {
            return $templates;
        }
    }

    /**
     * Sets the id of the active template. If the provided id is null or 0 (zero) and
     * the current template is not the default template, this template will be cleared.
     * It will then load the default template in [[Big::parseResponse()]].
     *
     * @param int|TemplateManagerObject $id id of a template or a template manager object.
     */
    public function setActive($id)
    {
        if ($id instanceof TemplateManagerObject) {
            $this->_active = $id;
        } elseif ($id) {
           $this->load($id);
        } elseif (!$this->getActive()->getIsDefault()) {
            $this->reset();
        }
    }

    /**
     * Returns the active template.
     *
     * @return null|TemplateManagerObject
     */
    public function getActive()
    {
    	return $this->_active;
    }

    /**
     * Loads a template from the database.
     * If no id is provided the default template will be loaded.
     *
     * @param int $id optional id of a template.
     * @return TemplateManagerObject a template manager object possibly as an updated instance.
     */
    public function load($id = 0)
    {
        $id = (int)$id;
        $active = $this->getActive();
        if (($id && $active->id === $id) || $active->getIsDefault()) {
            return $active;
        }

        $query = $this->find();
        if ($id) {
            $data = $query->where(['t.id' => $id])->one();
        } else {
            $data = $query->where(['t.is_default' => 1])->one();
        }
        if ($data) {
            $active = $this->configure($data);
        }
        return $active;
    }

    /**
     * Configures a template manager object according to the provided data and registers it as active.
     *
     * @param array $data the data to assign to use in the active template.
     * The data must contain the following:
     * ~~~php
     * [
     *     'id' => 1,
     *     'title' => 'The title',
     *     'positions' => [
     *         'POSITION NAME' => [
     *             BLOCK ID,
     *             BLOCK ID,
     *             ...
     *         ],
     *     ],
     *     'is_default' => 0 OR 1,
     * ]
     * ~~~
     */
    public function configure(array $data)
    {
        $data['id'] = (int)$data['id'];
        $data['positions'] = !empty($data['positions']) ? $data['positions'] : [];
        $data['is_default'] = (int)$data['is_default'];
        if (is_string($data['positions'])) {
            $data['positions'] = Json::decode($data['positions']);
        }

        $active = $this->createObject($data);
        $this->setActive($active);
        return $active;
    }

    /**
     * Resets the current template by setting an empty template as active.
     */
    public function reset()
    {
        $this->setActive($this->createObject([
            'id' => 0,
            'title' => '',
            'positions' => [],
            'is_default' => 0,
        ]));
    }

    /**
     * Creates an item object used when creating a template.
     *
     * @param array $data configuration array for the object.
     * @return TemplateManagerObject a template manager object.
     * @see [[createTree()]]
     */
    public function createObject(array $data)
    {
        return Yii::createObject([
            'class' => $this->itemClass
        ], [$data]);
    }

    /**
     * Return a query ready for the [[tableName]] database table.
     *
     * @return Query
     */
    public function find()
    {
        $query = new Query();
        $query->from($this->tableName.' t');
        return $query;
    }

    /**
     * Returns a template model. If id is provided the model will be loaded from the database.
     *
     * @param int $id optional id of a database record to load.
     * @return bigbrush\big\models\Template 
     * @throws InvalidParamException if model was not found in the database.
     */
    public function getModel($id = 0)
    {
        $model = Yii::createObject(['class' => $this->modelClass]);
        if (!$id) {
            return $model;
        } elseif ($model = $model->findOne($id)) {
            return $model;
        } else {
            throw new InvalidParamException("Model with id: '$id' not found.");
        }
    }
}