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
use yii\behaviors\TimestampBehavior;
use yii\helpers\Json;
use creocoder\nestedsets\NestedSetsBehavior;

/**
 * Category
 *
 * @property integer $id
 * @property string $module
 * @property string $title
 * @property string $content
 * @property int $state
 * @property int $tree
 * @property int $lft
 * @property int $rgt
 * @property int $depth
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $alias
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $params
 */
class Category extends ActiveRecord
{
    const STATE_ACTIVE = 1;
    const STATE_INACTIVE = 2;

    /**
     * @var int defines the id of a category that this category is a child of.
     */
    public $parent_id;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%category}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'title' => Yii::t('big', 'Title'),
            'content' => Yii::t('big', 'Content'),
            'name' => Yii::t('big', 'Name'),
            'state' => Yii::t('big', 'State'),
            'alias' => Yii::t('big', 'Alias'),
            'meta_title' => Yii::t('big', 'Meta title'),
            'meta_description' => Yii::t('big', 'Meta description'),
            'meta_keywords' => Yii::t('big', 'Meta keywords'),
        ];
    }

    /**
     * Returns an array used in dropdown lists for field [[state]]
     *
     * @return array
     */
    public function getStateOptions()
    {
        return [
            self::STATE_ACTIVE => 'Active',
            self::STATE_INACTIVE => 'Inactive',
        ];
    }

    /**
     * Returns the text value of the [[state]] property.
     * 
     * @return string the [[state]] property as a string representation.
     */
    public function getStateText()
    {
        $options = $this->getStateOptions();
        return isset($options[$this->state]) ? $options[$this->state] : '';
    }

    /**
     * Returns the "created_at" property as a formatted date.
     *
     * @return string a formatted date.
     */
    public function getCreatedAtText()
    {
        return Yii::$app->getFormatter()->asDateTime($this->created_at);
    }

    /**
     * Returns the "created_at" property as a formatted date.
     *
     * @return string a formatted date.
     */
    public function getUpdatedAtText()
    {
        return Yii::$app->getFormatter()->asDateTime($this->updated_at);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['title', 'required'],
            ['parent_id', 'integer'],
            ['content', 'filter', 'filter' => '\yii\helpers\HtmlPurifier::process'],
            [['title', 'meta_title', 'meta_description', 'meta_keywords', 'module'], 'string', 'max' => 255],
            ['params', 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
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
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->params = Json::encode($this->params);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->params = Json::decode($this->params);
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new NestedSetQuery(get_called_class());
    }
}