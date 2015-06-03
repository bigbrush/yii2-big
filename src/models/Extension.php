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
 * Extension
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $type
 * @property string $namespace
 * @property integer $state
 */
class Extension extends ActiveRecord
{
    const TYPE_BLOCK = 'block';

    const STATE_INACTIVE = 0;
    const STATE_ACTIVE = 1;


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => Yii::t('big', 'Name'),
            'type' => Yii::t('big', 'Type'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'type', 'namespace'], 'required'],
            ['description', 'filter', 'filter' => '\yii\helpers\HtmlPurifier::process'],
            ['type', 'in', 'range' => array_keys($this->getTypeOptions())],
            ['state', 'default', 'value' => static::STATE_ACTIVE],
            ['state', 'in', 'range' => array_keys($this->getStateOptions())],
        ];
    }

    /**
     * Returns an array of available states.
     * Can be used with form dropdown lists.
     *
     * @return array list of available states
     */
    public static function getTypeOptions()
    {
        return [
            static::TYPE_BLOCK => Yii::t('big', 'Block'),
        ];
    }

    /**
     * Returns the text value of the provided type. If type is not provided the value of [[type]] is returned as text.
     *
     * @param int $type an optional type id to get the value from.
     * @return string the text value.
     */
    public function getTypeText($type = null)
    {
        if ($type === null) {
            $type = $this->type;
        }
        $options = $this->getTypeOptions();
        return $options[$type];
    }

    /**
     * Returns an array of available states.
     * Can be used with form dropdown lists.
     *
     * @return array list of available states
     */
    public static function getStateOptions()
    {
        return [
            static::STATE_ACTIVE => Yii::t('big', 'Active'),
            static::STATE_INACTIVE => Yii::t('big', 'Inactive'),
        ];
    }

    /**
     * Returns the text value of the provided state. If state is not provided the value of [[state]] is returned as text.
     *
     * @param int $state an optional state id to get the value from.
     * @return string the text value.
     */
    public function getStateText($state = null)
    {
        if ($state === null) {
            $state = $this->state;
        }
        $options = $this->getStateOptions();
        return $options[$state];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%extension}}';
    }
}
