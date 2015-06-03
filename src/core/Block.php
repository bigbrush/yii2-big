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
 *
 * @property ActiveRecord $model
 * @property integer $blockId
 * @property string $title
 * @property boolean $showTitle
 * @property string $content
 * @property string $namespace
 * @property string $scope
 * @property boolean $isEnabled
 */
abstract class Block extends Widget implements BlockInterface
{
    /**
     * @var Block the model used with this block.
     */
    public $model;


    /**
     * Runs before [[model]] is saved but after it has validated.
     * Event handler for ActiveRecord::EVENT_BEFORE_INSERT and ActiveRecord::EVENT_BEFORE_UPDATE
     * which is registered in BlockManager::createBlock().
     *
     * @param yii\base\ModelEvent the event being triggered.
     */
    public function onBeforeSave($event)
    {
        $event->isValid = $this->save($event->sender);
    }

    /**
     * This method gets called right before a block model is saved. The model is validated at this point.
     * In this method any Block specific logic should run. For example saving a block specific model.
     * 
     * @param bigbrush\big\models\Block the model being saved.
     * @return boolean whether the current save procedure should proceed. If any block.
     * specific logic fails false should be returned - i.e. return $blockSpecificModel->save();
     */
    public function save($model)
    {
        return true;
    }

    /**
     * Returns a boolean indicating whether the block will render a form when being created/edited. If false is returned
     * the [[edit()]] method is called within a form where required fields are added automatically. In this case the block should only
     * render form fields related to the block. If true is returned the [[edit()]] method is called without any additional
     * HMTL markup added. The block then has complete control over the UI when editing.
     *
     * Should be used by a controller to determine how to render the block when it is being created/edited.
     *
     * @return boolean when true is returned the block being edited should render a form. When false is
     * returned [[edit()]] will be called within a form. Defaults to false.
     */
    public function getEditRaw()
    {
        return false;
    }

    /**
     * Returns the id of this block.
     *
     * @return string the block title.
     */
    public function getBlockId()
    {
        return $this->model->id;
    }

    /**
     * Returns the title of this block.
     *
     * @return string the block title.
     */
    public function getTitle()
    {
        return $this->model->title;
    }

    /**
     * Returns a boolean indicating whether the title should be visible in this block.
     *
     * @return boolean true if title should be visible and false if not.
     */
    public function getShowTitle()
    {
        return (bool)$this->model->show_title;
    }

    /**
     * Returns the content of this block.
     *
     * @return string the block content.
     */
    public function getContent()
    {
        return $this->model->content;
    }

    /**
     * Returns the scope of this block.
     *
     * @return string the scope of this block.
     */
    public function getScope()
    {
        return $this->model->scope;
    }

    /**
     * Returns a boolean indicating whether this block is enabled.
     *
     * @return boolean true if this block is enabled and false if not.
     */
    public function getIsEnabled()
    {
        return (bool)$this->model->state;
    }

    /**
     * Returns the namespace of this block.
     *
     * @return string the namespace of this block.
     */
    public function getNamespace()
    {
        return $this->model->namespace;
    }
}
