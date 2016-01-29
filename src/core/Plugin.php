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
use yii\base\ViewContextInterface;

/**
 * Plugin is the base class of all plugins. It implements [[PluginInterface]] which makes it compatible
 * with [[PluginManager]]. It also implements [[ViewContextInterface]] which makes plugins support
 * relative view names.
 *
 * A plugin is not required to extend this class, but if it does, it can use the familiar [[render()]] method
 * known from controllers and widgets in Yii2. If your plugin subclasses [[Plugin]] the method
 * [[PluginInterface::register($manager)]] must be implemented.
 *
 * If a plugin is being used as event handler for events triggered through other objects than the [[PluginManager]]
 * then event handlers are supposed to be registered in the init() method.
 */
abstract class Plugin extends Object implements PluginInterface, ViewContextInterface
{
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
}
