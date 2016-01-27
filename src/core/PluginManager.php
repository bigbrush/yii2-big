<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use DirectoryIterator;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\base\Component;
use yii\base\Event;

/**
 * PluginManager is a simple but highly flexible manager to handle automated plugins. It automatically
 * loads plugins and gives each plugin the option to register itself as event handler to triggered events.
 *
 * You can use the plugin manager just like regular events in Yii 2, the important difference is the
 * [[group()]] method. This sets the group of plugins the manager will target.
 *
 * ~~~php
 * // example one - without an event object
 * $manager = Yii::$app->big->pluginManager->group('pluginGroup')->trigger('nameOfEventToTrigger');
 * 
 * // example two - with a custom event object
 * use my\custom\events\MyEvent;
 * $myEvent = new MyEvent([
 *     'price' => 100,
 *     'quantity' => 2,
 * ]);
 * Yii::$app->big->pluginManager->trigger('nameOfEventToTrigger', $myEvent);
 * // plugins can then modify parameters in $myEvent which can be retrieved later like so
 * $price = $myEvent->price;
 * 
 * // example three - with a custom folder for plugins
 * $manager = Yii::$app->big->pluginManager->group('pluginGroup');
 * $manager->pluginsFolder = '@my/custom/plugin/folder';
 * $manager->trigger('user.saved');
 * ~~~ 
 * 
 * For more information on events see the [Yii documentation on Events](http://www.yiiframework.com/doc-2.0/guide-concept-events.html).
 */
class PluginManager extends Component
{
    /**
     * @var string $group name of the plugin group to search for plugins in. A plugin group equals a folder
     * within [[pluginsFolder]].
     */
    public $group;
    /**
     * @var string $pluginsFolder path alias of the folder storing all plugin groups. This property must be set
     * before using the plugin manager.
     * For instance: @app/plugins
     */
    public $pluginsFolder = '@app/plugins';
    /**
     * @var string $filename the name of the bootstrap file for each plugin.
     */
    public $filename = 'Plugin';


    /**
     * Registers the specified group as active in this plugin manager.
     *
     * @param string $group a group of plugins.
     * @return PluginManager this object to support chaining.
     */
    public function group($group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Triggers a plugin event in Big.
     * This override locates all plugins within [[group]] and automatically attaches them to this manager
     * as event handlers. Each plugin is only attached as an event handler if it cotains a method with the same name
     * as the provided event name.
     *
     * @param string $name the event name.
     * @param Event $event the event parameter. If not set, a default [[Event]] object will be created.
     * @throws yii\base\InvalidConfigException if [[group]] or [[pluginsFolder]] is not set in this manager.
     */
    public function trigger($name, Event $event = null)
    {
        if (!$this->group || !$this->pluginsFolder) {
            throw new InvalidConfigException('The properties "$group" and "$pluginsFolder" must be set in ' . get_class($this));
        }
        foreach ($this->findPlugins($this->group) as $plugin) {
            $plugin->register($this);
        }
        parent::trigger($name, $event);
    }

    /**
     * Crawls the provided plugin group for installed plugins. All found plugins are instantiated
     * giving the options of using the init method to setup prior to the event handler being called.
     *
     * @param string $group the plugin group to search for plugins in.
     * @return array list of instantiated plugins.
     * @throws yii\base\InvalidConfigException if provided plugin group is not a folder within [[pluginsFolder]].
     */
    public function findPlugins($group)
    {
        $folder = Yii::getAlias($this->pluginsFolder . '/' . $group);
        if (!is_dir($folder)) {
            throw new InvalidConfigException('Plugin group does not exist: "'. $folder . '"');
        }

        $plugins = [];
        $dirs = new DirectoryIterator($folder);
        foreach ($dirs as $dir) {
            if (!$dir->isDot() && $dir->isDir()) {
                $file = $dir->getPathname() . DIRECTORY_SEPARATOR . $this->filename . '.php';
                if (is_file($file)) {
                    // remove "@" and replace "/" with "\" in current plugin folder to convert it into a namespace
                    $namespace = str_replace('/', '\\', substr($this->pluginsFolder, 1));
                    // append plugin group and plugin bootstrap file name
                    $namespace .= '\\' . $group . '\\' . $dir->getFilename() . '\\' . $this->filename;
                    $plugin = Yii::createObject([
                        'class' => $namespace,
                    ]);
                    if ($plugin instanceof PluginInterface) {
                        $plugins[] = $plugin;
                    } else {
                        throw new InvalidValueException("Plugin '".get_class($plugin)."' must implement bigbrush\cms\components\PluginInterface");
                    }
                }
            }
        }
        return $plugins;
    }
}
