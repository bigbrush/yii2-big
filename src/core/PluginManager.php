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
use yii\base\Component;
use yii\base\Event;

/**
 * PluginManager is a simple but highly flexible manager to handle automated plugins. It automatically
 * loads plugins and gives each plugin the option to register itself as event handler in the plugin manager.
 *
 * You can use the plugin manager just like regular events in Yii 2. The [[group]] property needs to be defined
 * as it defines which folder, within [[pluginsFolder]], to target plugins.
 *
 * ~~~php
 * // example one - without an event object
 * Yii::$app->big->getPluginManager('pluginGroup')->trigger('user.saved');
 * 
 * // example two - with a custom event object
 * use my\custom\events\MyEvent;
 * $myEvent = new MyEvent([
 *     'price' => 100,
 *     'quantity' => 2,
 * ]);
 * Yii::$app->big->getPluginManager('pluginGroup')->trigger('user.saved', $myEvent);
 * // plugins can then modify parameters in $myEvent which can be retrieved like so
 * $price = $myEvent->price;
 * 
 * // example three - with a custom folder for plugins
 * $manager = Yii::$app->big->getPluginManager('pluginGroup');
 * $manager->pluginsFolder = '@app/my/custom/plugin/folder';
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
     * @var string $filename name of the bootstrap file (without the file extension) for each plugin.
     */
    public $filename = 'Plugin';


    /**
     * Triggers a plugin event.
     * This method override locates all plugins within [[group]] and automatically allows each one to register itself
     * as event handler in this manager.
     * 
     * A plugin must implement the [[PluginInterface]] to be compatible with this manager. It definess the register() method
     * which gives each plugin the option to register itself as event handler in the plugin manager.
     *
     * @param string $name the event name.
     * @param Event $event the event parameter. If not set, a default [[Event]] object will be created.
     * @throws yii\base\InvalidConfigException if [[group]] or [[pluginsFolder]] is not set in this manager.
     */
    public function trigger($name, Event $event = null)
    {
        if (!$this->pluginsFolder) {
            throw new InvalidConfigException('The property "$pluginsFolder" must be set in ' . get_class($this) . '.');
        } elseif (!$this->group) {
            throw new InvalidConfigException('The property "$group" must be set in ' . get_class($this) . '.');
        }
        $this->activateGroup($this->group);
        parent::trigger($name, $event);
    }

    /**
     * Activates a plugin group by calling the register() method on each plugin.
     *
     * @param string $group the plugin group to activate.
     */
    public function activateGroup($group)
    {
        foreach ($this->findPlugins($group) as $plugin) {
            if ($plugin instanceof PluginInterface) {
                $plugin->register($this);
            }
        }
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
                    $plugins[] = Yii::createObject([
                        'class' => $namespace,
                    ]);
                }
            }
        }
        return $plugins;
    }
}
