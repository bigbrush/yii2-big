<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

/**
 * BlockInterface ensures that a block is compatible with the [[BlockManager]] as well as provide
 * methods that enables a block to be edited and displayed.
 */
interface BlockInterface
{
    /**
     * Executes this block.
     *
     * @return string the result of block execution to be outputted.
     */
    public function run();

    /**
     * Edits this block.
     *
     * @param \bigbrush\big\models\Block $model the model for this block
     * @param yii\bootstrap\ActiveForm $form the form used when editing the block. Only has effect when
     * [[getEditRaw()]] returns true. Otherwise this parameter will be null.
     * @return string the result of block execution to be outputted.
     */
    public function edit($model, $form);

    /**
     * Runs before [[\bigbrush\big\models\Block]] is saved but after it has validated.
     * Event handler for ActiveRecord::EVENT_BEFORE_INSERT and ActiveRecord::EVENT_BEFORE_UPDATE
     * which is registered in [[BlockManager::createObject()]].
     *
     * @param ModelEvent $event the event being triggered
     */
    public function onBeforeSave($event);

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

    /**
     * Sets a model in this block.
     *
     * @param \yii\db\ActiveRecord $model a model to register in this block.
     */
    public function setModel($model);

    /**
     * Returns the model used in this block.
     *
     * @return \yii\db\ActiveRecord the model registered to this block.
     */
    public function getModel();
}
