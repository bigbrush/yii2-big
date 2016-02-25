<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\Object;
use yii\base\InvalidConfigException;

/**
 * ConfigManagerRule represents a simple rule set for config fields. By specifying [[lockedFields]] certain
 * fields can be locked and therefore not deleted. If the user is not allowed to change the
 * locked fields set [[changeLockedFields]] to false.
 */
class ConfigManagerRule extends Object implements ConfigManagerRuleInterface
{
    /**
     * @var array $lockedFields list of field names that cannot be deleted.
     */
    public $lockedFields = [];
    /**
     * @var bool $changeLockedFields defines whether [[lockedFields]] can be changed. True if they can
     * and false if not. Defaults to true.
     */
    public $changeLockedFields = true;
    /**
     * @var string $message most recent message of this rule.
     */
    protected $message;


    /**
     * Registers validation rules used in this config rule. These rules is used when validating
     * in [[onBeforeSave()]] and [[onBeforeSave()]].
     *
     * @param array $rules an array of rules for this config rule.
     */
    public function setRules($rules)
    {
        Yii::configure($this, $rules);
        if (!is_array($this->lockedFields)) {
            throw new InvalidConfigException('The property "lockedFields" must be an array in ' . get_class($this) . '.');
        }
    }

    /**
     * Validates that the specified model can be updated.
     * If the model is a new record this method always returns true.
     *
     * @param yii\db\ActiveRecord $model a model to validate.
     * @return bool true if model can be updated, false if not.
     */
    public function onBeforeSave($model)
    {
        if (!$model->getIsNewRecord() && in_array($model->id, $this->lockedFields) && !$this->changeLockedFields) {
            $this->message = Yii::t('big', 'Config "{name}" is locked and cannot be changed.', ['name' => $model->id]);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Validates that the specified model can be deleted.
     *
     * @param yii\db\ActiveRecord $model a model to validate.
     * @return bool true if model can be deleted, false if not.
     */
    public function onBeforeDelete($model)
    {
        if (in_array($model->id, $this->lockedFields)) {
            $this->message = Yii::t('big', 'Config "{name}" is locked and cannot be deleted.', ['name' => $model->id]);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Returns the most recent message of this rule.
     *
     * @return string a message.
     */
    public function getMessage()
    {
        return $this->message;
    }
}
