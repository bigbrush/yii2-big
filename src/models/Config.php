<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Config
 *
 * @property string $id
 * @property string $value
 * @property string $section
 */
class Config extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%config}}';
    }

    /**
     * @inheritdoc
     */
    public static function primaryKey()
    {
        return ['id', 'section'];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('big', 'Name'),
            'value' => Yii::t('big', 'Value'),
            'section' => Yii::t('big', 'Section'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['id', 'required'],
            ['value', 'default', 'value' => ''],
            ['value', 'string'],
            [['id', 'section'], 'string', 'max' => 255],
        ];
    }
}
