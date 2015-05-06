<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\Object;
use yii\db\Query;
use yii\helpers\Json;

/**
 * Template
 */
class Template extends Object
{
    /**
     * @var string name of a database table to load templates from.
     */
    public $tableName = 'template';
    /**
     * @var string represents the model class when creating/editing a template.
     * The table is aliased by the letter "t".
     */
    public $modelClass = 'bigbrush\big\models\Template';
    /**
     * @var int the id of this template
     */
    private $_id;
    /**
     * @var string the title of this template.
     */
    private $_title;
    /**
     * @var string positions assigned to this template.
     * Format: [
     *     'POSITION NAME' => [
     *         BLOCK ID,
     *         BLOCK ID,
     *         ...
     *     ],
     *     ...
     * ]
     */
    private $_positions = [];
    /**
     * @var int defines whether this template is the default template.
     */
    private $_isDefault;


    /**
     * Sets the id of the active template. If the provided id is null or 0 (zero)
     * this template will be cleared. It will then load the default template in
     * [[Big::parseResponse()]].
     *
     * @param int $id id of a template.
     */
    public function setActive($id)
    {
        if ($id) {
           $this->load($id);
        } elseif (!$this->getIsDefault()) {
            $this->clear();
        }
    }

    /**
     * Loads a template from the database.
     * If no id is provided the default template will be loaded.
     *
     * @param mixed $id optional id of a template.
     * @return Template this object possibly as an updated instance.
     */
    public function load($id = 0)
    {
        $id = (int)$id;
        if ($this->_id && !$id || $this->_id === $id) {
            return $this;
        }
        $query = $this->find();
        if ($id) {
            $data = $query->where(['t.id' => $id])->one();
        } else {
            $data = $query->where(['t.is_default' => 1])->one();
        }
        if ($data) {
            $this->configure($data);
        }
        return $this;
    }

    /**
     * Configures this template according to the provided data.
     *
     * @param array $data the data to assign to this template.
     */
    public function configure($data)
    {
        $this->_id = (int)$data['id'];
        $this->_title = $data['title'];
        $this->_positions = !empty($data['positions']) ? $data['positions'] : [];
        $this->_isDefault = (int)$data['is_default'];
        if (is_string($this->_positions)) {
            $this->_positions = Json::decode($this->_positions);
        }
    }

    /**
     * Resets the current template
     */
    public function clear()
    {
        $this->_id = null;
        $this->_title = null;
        $this->_positions = [];
        $this->_isDefault = null;
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
     * Returns the id of this template.
     *
     * @return int the template id
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Returns the title of this template
     *
     * @return string the template title
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Returns all positions and assigned blocks used in this template.
     * This method returns an array where the keys are position names and
     * the values are arrays of blocks ids assigned to the position.
     *
     * If an array of positions is provided only mathcing positions from this
     * template is returned. If none of the provided positions is assigned an
     * empty array is returned.
     *
     * @param array $names optional list of position names.
     * If not provided all positions assigned in this template is returned.
     * @return array list of positions assigned to this template.
     * @see [[getPosition()]]
     */
    public function getPositions(array $names = [])
    {
        if (empty($names)) {
            return $this->_positions;
        }

        $positions = [];
        foreach ($names as $name) {
            $ids = $this->getPosition($name);
            if (!empty($ids)) {
                $positions[$name] = $ids;
            }
        }
        return $positions;
    }

    /**
     * Returns blocks ids assigned to the provided position. If the provided position
     * is not registered in this template an empty array is returned.
     *
     * @param string $name a position name
     * @return array an array of block ids if the position exists. An empty array if
     * the provided position is not registered.
     */
    public function getPosition($name)
    {
        if (isset($this->_positions[$name])) {
            return $this->_positions[$name];
        } else {
            return [];
        }
    }

    /**
     * Returns true if this template is the default template.
     *
     * @return boolean true if this is the default template, otherwise false.
     */
    public function getIsDefault()
    {
        return $this->_isDefault === 1;
    }

    /**
     * Returns a template model.
     * If no id is provided the default template will be loaded.
     *
     * @param int $id optional id of a template. If 0 (zero) is provided
     * a new template will be created.
     * @return Template
     */
    public function getModel($id = null)
    {
        $model = Yii::createObject($this->modelClass);
        if ($id) {
            return $model->findOne($id);
        } elseif ($id === null) {
            return $model->find()->where(['is_default' => 1])->one();
        } else {
            return $model;
        }
    }
}
