<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\models;

use yii\db\ActiveRecord;
use yii\helpers\Json;

/**
 * Template
 *
 * @property integer $id
 * @property string $title
 * @property string $positions
 * @property integer $is_default
 */
class Template extends ActiveRecord
{
    const UNREGISTERED = 'UNREGISTERED';

    private $_cachedAttributes;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%template}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['title', 'required'],
            ['positions', 'validatePositions'],
            ['is_default', 'default', 'value' => 0],
            ['is_default', 'integer', 'min' => 0, 'max' => 1],
            ['is_default', 'validateDefault'],
        ];
    }

    /**
     * Validates that [[is_default]] is not changed on a default template.
     *
     * @param string $attribute the attribute to validate
     * @param array $params parameters for the validation rule
     */
    public function validateDefault($attribute, $params)
    {
        if ($this->_cachedAttributes['is_default'] && !$this->is_default) {
            $this->addError($attribute, 'Cannot reset a default template.');
        }
    }

    /**
     * Validates positions assigned to this template.
     *
     * @param string $attribute the attribute to validate.
     * @param array $params parameters for the validation rule.
     */
    public function validatePositions($attribute, $params)
    {
        foreach ($this->positions as $name => $ids) {
            if(array_filter($ids, 'is_numeric') !== $ids) {
                $this->addError($attribute, 'Blocks must be registered with an id as integer.');
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $positions = $this->positions;
            if (isset($positions[static::UNREGISTERED])) {
                unset($positions[static::UNREGISTERED]);
            }
            $this->positions = Json::encode($positions);
            if ($this->is_default && ($this->getIsNewRecord() || !$this->_cachedAttributes['is_default'])) {
                $model = $this->find()->where(['is_default' => 1])->one();
                if ($model) { // do not exist if a default is not set
                    $model->is_default = 0;
                    return $model->update(false, ['is_default']) !== false;
                }
            }
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
        $this->positions = Json::decode($this->positions);
        $this->_cachedAttributes = $this->getAttributes(['is_default']);
    }
}