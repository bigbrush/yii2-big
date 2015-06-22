<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

/**
 * Interface for a [[Block]] that ensures backend compatability.
 */
interface BlockInterface
{
    /**
     * Runs before [[bigbrush\big\models\Block]] is saved but after it has validated.
     * Event handler for ActiveRecord::EVENT_BEFORE_INSERT and ActiveRecord::EVENT_BEFORE_UPDATE
     * which is registered in [[BlockManager::createObject()]].
     *
     * @param ModelEvent the event being triggered
     */
    public function onBeforeSave($event);

    /**
     * Returns a html form used when editing a block.
     *
     * @param Block $model the model for this block
     * @param yii\bootstrap\ActiveForm $form the form used when editing the block. Only has effect when
     * [[getEditRaw()]] returns true. Otherwise this parameter will be null.
     * @return string html form ready to be rendered.
     */
    public function edit($model, $form);

    /**
     * Returns a boolean indicating whether the block will render a form when being created/edited. If false is returned
     * the [[edit()]] method is called within a form where required fields are added automatically. In this case the block should only
     * render form fields related to the block. If true is returned the [[edit()]] method is called without any additional
     * HMTL markup added. The block then has complete control over the UI when editing.
     *
     * @return boolean when true is returned the block being edited should render a form. When false is
     * returned [[edit()]] will be called within a form.
     */
    public function getEditRaw();
}
