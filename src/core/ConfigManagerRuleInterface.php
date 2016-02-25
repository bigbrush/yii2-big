<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

/**
 * ConfigManagerRuleInterface defines a set of methods that needs to be implemented in order for an
 * object to be compatible with [[ConfigManager]]. It is specifically used when the [[ConfigManager]]
 * creates, updates or deletes config entries.
 */
interface ConfigManagerRuleInterface
{
    /**
     * Registers rules in this config rule. These rules is used when validating
     * in [[onBeforeSave()]] and [[onBeforeSave()]].
     *
     * @param array $rules an array of rules for this config rule.
     */
    public function setRules($rules);

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
     * Returns the most recent message of this rule.
     *
     * @return string a message.
     */
    public function getMessage();
}
