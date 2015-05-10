<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\SluggableBehavior;
use creocoder\nestedsets\NestedSetsBehavior;

/**
 * Menu
 *
 * @property integer $id
 * @property string $title
 * @property string $alias
 * @property string $route
 * @property int $state
 * @property int $tree
 * @property int $lft
 * @property int $rgt
 * @property int $depth
 * @property int $is_default
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 */
class Menu extends ActiveRecord
{
    const SCENARIO_MENU = 'menu';
    
    const STATE_THRASHED = 0;
    const STATE_ACTIVE = 1;
    const STATE_INACTIVE = 2;

    /**
     * @var int defines the id of the menu this item is connected to.
     */
    public $menu_id;
    /**
     * @var int defines the id of a menu item that this menu item is a child of.
     */
    public $parent_id;

    private $_cachedAttributes;

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return [
            static::SCENARIO_DEFAULT  => ['title', 'menu_id', 'state', 'parent_id', 'route', 'is_default', 'meta_title', 'meta_description', 'meta_keywords'],
            static::SCENARIO_MENU => ['title'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'route'], 'required'],
            ['state', 'default', 'value' => static::STATE_ACTIVE],
            ['state', 'in', 'range' => array_keys($this->getStateOptions())],
            ['menu_id', 'integer', 'min' => 1, 'tooSmall' => 'Choose a menu for this item'],
            [['parent_id', 'is_default'], 'integer'],
            [['meta_title', 'meta_description', 'meta_keywords'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'tree' => [
                'class' => NestedSetsBehavior::className(),
                'treeAttribute' => 'tree',
            ],
            [
                'class' => SluggableBehavior::className(),
                'attribute' => 'title',
                'slugAttribute' => 'alias',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'menu_id' => 'Menu',
            'parent_id' => 'Parent',
        ];
    }

    /**
     * Returns an array of available states.
     * Can be used with form dropdown lists.
     *
     * @return array list of available states
     */
    public function getStateOptions()
    {
        return [
            static::STATE_THRASHED => 'Thrashed',
            static::STATE_ACTIVE => 'Active',
            static::STATE_INACTIVE => 'Inactive',
        ];
    }

    /**
     * Returns an array of value form [[is_default]].
     * Can be used with form dropdown lists.
     *
     * @return array list of available options
     */
    public function getIsDefaultOptions()
    {
        return [
            'No',
            'Yes',
        ];
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            static::SCENARIO_DEFAULT => static::OP_ALL,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new MenuQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->is_default && ($this->getIsNewRecord() || !$this->_cachedAttributes['is_default'])) {
            $model = $this->find()->where(['is_default' => 1])->one();
            // menu do not exist if a default is not set
            if ($model) {
                $model->is_default = 0;
                return $model->update(false, ['is_default']) !== false;
            }
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->_cachedAttributes = $this->getAttributes(['is_default']);
    }
}