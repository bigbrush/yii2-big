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
use yii\helpers\Json;
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
 * @property string $params
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
    public static function tableName()
    {
        return '{{%menu}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'title' => Yii::t('big', 'Title'),
            'alias' => Yii::t('big', 'Alias'),
            'route' => Yii::t('big', 'Route'),
            'state' => Yii::t('big', 'State'),
            'is_default' => Yii::t('big', 'Is default'),
            'meta_title' => Yii::t('big', 'Meta title'),
            'meta_description' => Yii::t('big', 'Meta description'),
            'meta_keywords' => Yii::t('big', 'Meta keywords'),
            'menu_id' => Yii::t('big', 'Menu'),
            'parent_id' => Yii::t('big', 'Parent'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return array_merge(parent::scenarios(), [
            static::SCENARIO_MENU => ['title'],
        ]);
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
            ['menu_id', 'integer', 'min' => 1, 'tooSmall' => Yii::t('big', 'Choose a menu for this item')],
            [['parent_id', 'is_default'], 'integer'],
            [['meta_title', 'meta_description', 'meta_keywords', 'alias'], 'string', 'max' => 255],
            ['params', 'safe'],
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
                'immutable' => true,
            ],
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
            static::STATE_THRASHED => Yii::t('big', 'Thrashed'),
            static::STATE_ACTIVE => Yii::t('big', 'Active'),
            static::STATE_INACTIVE => Yii::t('big', 'Inactive'),
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
            Yii::t('big', 'No'),
            Yii::t('big', 'Yes'),
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
        return new NestedSetQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->params = empty($this->params) ? '' : Json::encode($this->params);

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
        $this->params = empty($this->params) ? '' : Json::decode($this->params);
        $this->_cachedAttributes = $this->getAttributes(['is_default']);
    }
}