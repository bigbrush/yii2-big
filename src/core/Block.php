<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\Widget;

/**
 * Block
 */
abstract class Block extends Widget implements BlockInterface
{
    /**
     * @var Block the model used with this block
     */
    public $model;


    /**
     * Runs before [[model]] is saved but after it has validated.
     * Event handler for ActiveRecord::EVENT_BEFORE_INSERT and ActiveRecord::EVENT_BEFORE_UPDATE
     * which is registered in BlockManager::createBlock().
     *
     * @param yii\base\ModelEvent the event being triggered
     */
    public function onBeforeSave($event)
    {
        $event->isValid = $this->save($event->sender);
    }

    /**
     * This method gets called right before a block model is saved. The model is validated at this point.
     * In this method any Block specific logic should run. For example saving a block specific model.
     * 
     * @param bigbrush\big\models\Block the model being saved
     * @return boolean whether the current save procedure should proceed. If any block
     * specific logic fails false should be returned - i.e. return $blockSpecificModel->save();
     */
    public function save($model)
    {
        return true;
    }
}
