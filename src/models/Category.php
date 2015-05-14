<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\models;

use yii\db\ActiveRecord;
use yii\behaviors\SluggableBehavior;
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
 * @property string $alias
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
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
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['title', 'required'],
            ['parent_id', 'integer'],
            ['content', 'filter', 'filter' => '\yii\helpers\HtmlPurifier::process'],
            [['title', 'meta_title', 'meta_description', 'meta_keywords', 'module'], 'string', 'max' => 255],
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
}