<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

/**
 * BlockInterface ensures compatibility with [[BlockManager]].
 *
 * It extends from [[ViewContextInterface]] to provide support for relative views names
 * like regular Yii2 widgets.
 */
interface BlockInterface extends \yii\base\ViewContextInterface
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
    public function render($view, $params = []);

    /**
     * Runs before [[\bigbrush\big\models\Block]] is saved but after it has validated.
     * Event handler for ActiveRecord::EVENT_BEFORE_INSERT and ActiveRecord::EVENT_BEFORE_UPDATE
     * which is registered in [[BlockManager::createObject()]].
     *
     * @param ModelEvent the event being triggered
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
     * @param yii\db\ActiveRecord $model a model to register in this block.
     */
    public function setModel($model);

    /**
     * Returns the model used in this block.
     *
     * @return yii\db\ActiveRecord the model registered to this block.
     */
    public function getModel();
}
