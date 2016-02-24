<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use ReflectionClass;
use Yii;
use yii\base\Object;

/**
 * Block is considered as a building block in views and is primarily implemented by `include statements`.
 *
 * It resembles Yii2 widgets quite a bit. The main difference is that a Block doesn't provide the
 * `widget()` method like a Yii2 widget does.
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
abstract class Block extends Object implements BlockInterface
{
    /**
     * @var \bigbrush\big\models\Block the model assigned to this block.
     */
    private $_model;


    /**
     * Runs before [[model]] is saved but after it has validated.
     * Event handler for ActiveRecord::EVENT_BEFORE_INSERT and ActiveRecord::EVENT_BEFORE_UPDATE
     * which is registered in BlockManager::createBlock().
     *
     * @param \yii\base\ModelEvent the event being triggered.
     */
    public function onBeforeSave($event)
    {
        $event->isValid = $this->save($event->sender);
    }

    /**
     * This method gets called right before a block model is saved. The model is validated at this point.
     * In this method any Block specific logic should run. For example saving a block specific model.
     * 
     * @param \bigbrush\big\models\Block the model being saved.
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
     * Sets a model in this block.
     *
     * @param \yii\db\ActiveRecord $model a model to register to this block.
     */
    public function setModel($model)
    {
        $this->_model = $model;
    }

    /**
     * Returns the model used in this block.
     *
     * @return \yii\db\ActiveRecord the model regsitered to this block.
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * Renders a view.
     * The view to be rendered can be specified in one of the following formats:
     *
     * - path alias (e.g. "@app/views/site/index");
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
     *   The actual view file will be looked for under the [[Application::viewPath|view path]] of the application.
     * - absolute path within module (e.g. "/site/index"): the view name starts with a single slash.
     *   The actual view file will be looked for under the [[Module::viewPath|view path]] of the currently
     *   active module.
     * - relative path (e.g. "index"): the actual view file will be looked for under [[viewPath]].
     *
     * If the view name does not contain a file extension, it will use the default one `.php`.
     *
     * @param string $view the view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidParamException if the view file does not exist.
     */
    public function render($view, $params = [])
    {
        return Yii::$app->getView()->render($view, $params, $this);
    }

    /**
     * Returns the directory containing the view files for this widget.
     * The default implementation returns the 'views' subdirectory under the directory containing the widget class file.
     * @return string the directory containing the view files for this widget.
     */
    public function getViewPath()
    {
        $class = new ReflectionClass($this);
        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'views';
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
