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
     * which is registered in [[BlockManager::createBlock()]].
     *
     * @param ModelEvent the event being triggered
     */
    public function onBeforeSave($event);

    /**
     * Returns a html form used when editing a block.
     *
     * @param Block $model the model for this block
     * @return string html form ready to be rendered.
     */
    public function edit($model);
}
