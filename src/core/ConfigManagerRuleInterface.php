<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

/**
 * ConfigManagerRuleInterface defines a set of methods that needs to be implemented in order for an
 * objec to be compatible with [[ConfigManager]]. It is specifically used by [[bigbrush\big\models\Config]] 
 * before saving and deleting models.
 */
interface ConfigManagerRuleInterface
{
    /**
     * Registers configurations for this rule. These configurations can be used when validating models
     * in [[onBeforeSave()]] and [[onBeforeSave()]].
     *
     * @param array $config a configuration array for this rule.
     */
    public function setConfig($config);

    /**
     * Validates that the specified model can be saved/updated.
     *
     * @param yii\db\ActiveRecord $model a model to validate.
     * @return bool true if model can be saved/updated, false if not.
     */
    public function onBeforeSave($model);

    /**
     * Validates that the specified model can be deleted.
     *
     * @param yii\db\ActiveRecord $model a model to validate.
     * @return bool true if model can be deleted, false if not.
     */
    public function onBeforeDelete($model);

    /**
     * Returns the most message of this rule.
     *
     * @return string a message.
     */
    public function getMessage();
}
