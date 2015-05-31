<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\models;

use Yii;
use yii\db\ActiveRecord;
use bigbrush\big\models\BlockQuery;

/**
 * Block
 *
 * @property integer $id
 * @property string $title
 * @property string $content
 * @property string $name
 * @property integer $show_title
 */
class Block extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%block}}';
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
            'show_title' => Yii::t('big', 'Show title'),
        ];
    }

    /**
     * Returns the list of all attribute names of the model.
     * Method overridden to avoid a database call by parent implementation.
     * Block models are populated with data in [[bigbrush\big\core\BlockManager::registerPositions()]] to
     * avoid overhead from a database call. This method needs to be implemented to remove this overhead.
     *
     * @return array list of attribute names used by this AR
     */
    public function attributes()
    {
        return [
            'id',
            'title',
            'content',
            'name',
            'show_title',
            'state',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['title', 'required'],
            [['title', 'name'], 'string', 'max' => 255],
            ['content', 'default', 'value' => ''],
            ['show_title', 'integer'],
        ];
    }

    /**
     * Helper to determine whether to show the title
     *
     * @return boolean whether to show the title
     */
    public function getShowTitle()
    {
        return (bool)$this->show_title;
    }
}