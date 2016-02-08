<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * ConfigManagerRule represents a simple rule set for config fields. Certain config fields can be locked
 * by specifying "lockedFields" in [[config]]. If the user is not allowed to change the locked fields set
 * "changeLockedFields" in [[config]] to false.
 */
class ConfigManagerRule extends Behavior implements ConfigManagerRuleInterface
{
    /**
     * @var array $config configuration array for this config manager rule.
     */
    protected $config = [
        'lockedFields' => [],
        'changeLockedFields' => true,
    ];
    /**
     * @var string $message most recent message of this rule.
     */
    protected $message;


    /**
     * Registers configurations for this rule. These configurations can be used when validating models
     * in [[onBeforeSave()]] and [[onBeforeSave()]].
     *
     * @param array $config a configuration array for this rule.
     */
    public function setConfig($config)
    {
        $this->config = ArrayHelper::merge($this->config, $config);
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
        if (!$model->getIsNewRecord() && in_array($model->id, $this->config['lockedFields'])) {
            $this->message = Yii::t('big', 'Config "{name}" is locked and cannot be changed.', ['name' => $model->id]);
            return (bool)$this->config['changeLockedFields'];
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
        if (in_array($model->id, $this->config['lockedFields'])) {
            $this->message = Yii::t('big', 'Config "{name}" is locked and cannot be deleted.', ['name' => $model->id]);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Returns the most message of this rule.
     *
     * @return string a message.
     */
    public function getMessage()
    {
        return $this->message;
    }
}
