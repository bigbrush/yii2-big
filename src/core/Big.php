<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\di\ServiceLocator;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\base\InvalidCallException;
use yii\base\InvalidParamException;
use yii\helpers\Url;
use yii\helpers\FileHelper;
use yii\web\Application;
use yii\web\View;
use yii\web\Response;
use bigbrush\big\widgets\recorder\Recorder;

/**
 * Big is the core class of the Big framework. It provides an entry point to the framework
 * which primarily provides access to different managers.
 *
 * Big consists of the following managers:
 *
 *  - [BlockManager](bigbrush-big-core-blockmanager.html)
 *  - [CategoryManager](bigbrush-big-core-categorymanager.html)
 *  - [ConfigManager](bigbrush-big-core-configmanager.html)
 *  - [ExtensionManager](bigbrush-big-core-extensionmanager.html)
 *  - [MenuManager](bigbrush-big-core-menumanager.html)
 *  - [PluginManager](bigbrush-big-core-pluginmanager.html)
 *  - [TemplateManager](bigbrush-big-core-templatemanager.html)
 *  - [UrlManager](bigbrush-big-core-urlmanager.html)
 *
 * Big can be plugged into any Yii 2 application and is designed to be easy to integrate. It does
 * not require inheritance from any specific classes (other than Yii classes obviously).
 * Big reacts during runtime by implementing the [[BootstrapInterface]] to plug into
 * a running Yii application.
 *
 * Big should be configured through the application configurations, like so:
 *
 * ~~~php
 * ...
 * 'bootstrap' => [
 *     'big',
 * ],
 * ...
 * 'components' => [
 *     'big' => [
 *         'class' => 'bigbrush\big\core\Big',
 *     ],
 * ],
 * ...
 * ~~~
 *
 * By default Big is deactivated. This means that specific functionality of Big should be
 * enabled when needed. If for instance Big should automatically set the application default route
 * add the following to the application configuration:
 *
 * ~~~php
 * 'components' => [
 *     'big' => [
 *         'class' => 'bigbrush\big\core\Big',
 *         'setApplicationDefaultRoute' => true,
 *     ],
 * ],
 * ~~~
 *
 * Big will then use the default menu item of [[MenuManager]] to set the application default route.
 * As this will activate the [[MenuManager]] you could set the property [[MenuManager::autoload]]
 * to "true" so all menus are loaded in one swoop. This could be more efficient than loading each
 * menu on it's own. The decision is up to you.
 *
 * To enable autoload add this to the configuration:
 *
 * ~~~php
 * 'components' => [
 *     'big' => [
 *         'class' => 'bigbrush\big\core\Big',
 *         'setApplicationDefaultRoute' => true,
 *         'managers' => [
 *             'menuManager' => [
 *                 'autoload' => true,
 *             ],
 *         ],
 *     ],
 * ],
 * ~~~
 *
 * @author Michael Bech <mj@bigbrush-agency.com>
 */
class Big extends ServiceLocator implements BootstrapInterface
{
    /**
     * version
     */
    const BIG_VERSION = '1.2.3';

    /**
     * @var string path for the frontend theme. Is needed when identifing
     * the available positions in the frontend theme.
     */
    public $frontendTheme;
    /**
     * @var array list of event handlers used when searching.
     * This property can be used to add search event handlers dynamically or configured in the application configuration file.
     * @see http://www.yiiframework.com/doc-2.0/guide-concept-events.html#attaching-event-handlers.
     */
    public $searchHandlers = [];
    /**
     * @var boolean whether to enable dynamic content. Dynamic content is used when a theme/layout use include statements
     * or when content saved with the editor is being displayed.
     * When enabled the application response will be parsed by [[parser]].
     */
    public $enableDynamicContent = false;
    /**
     * @var string the class used when creating the a parser.
     * @see getParser()
     */
    public $parser = 'bigbrush\big\core\Parser';
    /**
     * @var boolean indicates whether the menu manager should set a default route in the running application.
     * If true the default menu item will determine the default route. It will only be set
     * when [[\yii\web\Request::pathinfo]] equals an empty string (meaning the application home page).
     * The [[UrlManager]] will also be activated by setting this to true.
     * @see [[\yii\web\Application::defaultRoute]].
     */
    public $setApplicationDefaultRoute = false;


    /**
     * Bootstraps Big by registering all managers in this locator. Also hooks into the main application
     * via the event system to parse layout files.
     * 
     * Is called after the application, and Big, has been configured.
     *
     * @param yii\base\Application $app the application currently running
     * @see http://www.yiiframework.com/doc-2.0/guide-structure-extensions.html#bootstrapping-classes
     */
    public function bootstrap($app)
    {
        // enable internationalization first so core classes can use it
        $this->registerTranslations([
            'class' => 'yii\i18n\PhpMessageSource',
            'basePath' => '@bigbrush/big/messages',
        ]);

        // check whether managers has been registered 
        if (!$this->has('urlManager')) {
            $this->setManagers([]);
        }

        // the url manager registers itself as an url rule in the running application if it is enabled.
        $this->getUrlManager();

        // register menu manager when Big performs a search
        $menuManager = $this->getMenuManager();
        $this->searchHandlers[] = [$menuManager, 'onSearch'];

        // set the application default route when enabled
        if ($this->setApplicationDefaultRoute && $app->getRequest()->pathInfo === '') {
            $menu = $menuManager->getDefault();
            $menuManager->setActive($menu);
            $params = $this->getUrlManager()->parseInternalUrl($menu->route);
            $app->defaultRoute = $params[0];
            unset($params[0]);
            foreach ($params as $key => $value) {
                $_GET[$key] = $value;
            }
        }

        // hook into the Yii application
        $this->registerApplicationHooks($app);
    }

    /**
     * Hooks into the running Yii application by registering various event handlers.
     * Registers event handlers for searches in Big.
     * Is called at the end of the boostrapping process.
     *
     * @param yii\web\Application $app the application currently running.
     */
    public function registerApplicationHooks($app)
    {
        $app->getView()->on(View::EVENT_BEGIN_PAGE, function($event) {
            // render blocks if the active theme has positions enabled
            $positions = $this->getActiveThemePositions();
            if (!empty($positions)) {
                $this->renderBlocks($positions);
            }

            // set the page title (if not set by a Block) when a layout starts to render.
            $view = $event->sender;
            $menu = $this->menuManager->getActive();
            if (empty($view->title) && $menu) {
                $view->title = (empty($menu->meta_title)) ? $menu->title : $menu->meta_title;
            }
        });

        if ($this->enableDynamicContent) {
            // register event handler that parses the application response
            $app->on(Application::EVENT_AFTER_REQUEST, [$this, 'parseResponse']);
        }
    }

    /**
     * Renders blocks assigned to positions in the active template.
     * This method loads a [[\bigbrush\big\models\Template]] if one has not been loaded yet.
     *
     * @param array $positions list of positions used in the active theme. See [[getThemePositions()]]
     * for information about the format of the array.
     */
    public function renderBlocks($positions)
    {
        // get the active template (is being loaded when no template has been assigned)
        $template = $this->getTemplate();
        // get active positions in the template
        $positions = $template->getPositions(array_keys($positions));
        // register positions in the block manager
        $this->blockManager->registerPositions($positions);

    }

    /**
     * Parses the application response.
     * This method changes the property yii\web\Response::data. The content of this property is
     * being displayed to the end user.
     *
     * @param yii\base\Event $event the event being triggered
     */
    public function parseResponse($event)
    {
        // skip parsing on Yii debug module
        // this method gets called twice - why is this happening?
        if ($event->sender->requestedRoute === 'debug/default/toolbar') {
            return;
        }
        $response = Yii::$app->getResponse();
        if ($response->format === Response::FORMAT_HTML && !empty($response->data)) {
            $response->data = $this->getParser()->run($response->data, $this->blockManager->getRegisteredBlocks());
        }
    }

    /**
     * Registers translations.
     *
     * @param array $config a configuration array used to add a translation source to the [[yii\i18n\I18N]] application component.
     */
    public function registerTranslations($config)
    {
        Yii::$app->i18n->translations['big'] = $config;
    }

    /**
     * Triggers a Big search event in the running Yii application. The first parameter defines the event
     * to trigger. The second parameter is a name for the event being triggered. The third parameter
     * defines whether to use dynamic urls in the search results. If dynamic urls are enabled all
     * urls will be converted to a non-seo format. See [[UrlManager::createInternalUrl()]] for
     * information about the url format.
     * Dynamic urls will be parsed by [[Parser::parseUrls()]] which is called by [[parseResponse()]].
     *
     * Widget [[\bigbrush\big\widgets\bigsearch\BigSearch]] can be used to make searches in Big.
     *
     * Example:
     *
     * ~~~
     * BigSearch::widget([
     *     'value' => 'The value to search for', // optional
     *     'dynamicUrls' => true // optional - defaults to false
     * ]);
     * ~~~
     *
     * To plug into the search event system in Big add the following to the application configuration file.
     *
     * ~~~php
     * return [
     *     'id' => 'APPLICATION ID',
     *     ...
     *     'on big.search' => function($event){
     *         $event->addItem([
     *             'title' => 'The title',
     *             'route' => ['app/page/show', 'id' => 3],
     *             'text' => 'Text or description of the item',
     *             'date' => 'An important date to the item (could be creation date)'
     *             'section' => 'The section of the item',
     *         ]);
     *     },
     *     'components' => [...],
     * ];
     * ~~~
     *
     * Another way to plug into the searches in Big is to add the following to the application configuration file.
     *
     * ~~~php
     * return [
     *     'id' => 'APPLICATION ID',
     *     ...
     *     'components' => [
     *         'big' => [
     *             'searchHandlers' => [
     *                 ['app\components\Bar', 'methodName'],
     *                 [$object, 'methodName'],
     *             ],
     *         ],
     *         ...
     *     ],
     * ];
     * ~~~
     *
     * @param Event $event an event object. If [[\bigbrush\big\widgets\bigsearch\BigSearch]] is used this
     * parameter is a [[SearchEvent]] object.
     * @param string $name the event name to trigger.
     * @param booloean $dynamicUrls if true all urls will have "index.php?r" prepended. See
     * [[UrlManager::createInternalUrl()]] for more information.
     * @return array list of search results.
     *
     * Default format of the returned array is: 
     *
     * ~~~php
     * [
     *     [
     *         'title' => 'Title for the item',
     *         'route' => 'Route for the item',
     *         'text' => 'Intro text for the item',
     *         'date' => 'Date for the item',
     *         'section' => 'The section this item belongs to',
     *     ],
     *     ...
     * ]
     * ~~~
     */
    public function search($event, $name, $dynamicUrls = false)
    {
        $app = Yii::$app;
        
        // register search event handlers
        if (!empty($this->searchHandlers)) {
            foreach ($this->searchHandlers as $handler) {
                $app->on(SearchEvent::EVENT_SEARCH, $handler);
            }
            $this->searchHandlers = [];
        }

        $event->sender = $this;
        $app->trigger($name, $event);
        $items = $event->items;
        if (!empty($items)) {
            $manager = $this->urlManager;
            foreach ($items as $i => $item) {
                $items[$i]['route'] = $manager->createInternalUrl($item['route'], $dynamicUrls);
            }
        }
        return $items;
    }

    /**
     * Determines whether a position is active in the current template.
     *
     * Use like the following:
     *
     * ~~~php
     * <?php if (Yii::$app->big->isPositionActive('sidebar')) : ?>
     * <div id="sidebar-wrapper">
     *     <big:include position="sidebar" />
     * </div>
     * <?php endif; ?>
     * ~~~
     *
     * @param string $position a position to determine whether is active in the current template.
     * @return boolean true if the position is active, false if not.
     */
    public function isPositionActive($position)
    {
        return !empty($this->getTemplate()->getPosition($position));
    }

    /**
     * Begins recording a block and automatically assigns it to the provided position.
     * A matching [[endBlock()]] call should be called later.
     *
     * Use this method to register blocks from anywhere within the application.
     *
     * For instance:
     *
     * ~~~
     * <?php Yii::$app->big->beginBlock('BLOCK POSITION'); ?>
     * <div class="recorded-block">
     *     <h3>Recorded block</h3>
     * </div>
     * <?php Yii::$app->big->endBlock(); ?>
     * ~~~
     *
     * @param string $position the block position.
     * @return Recorder the [[Recorder]] widget instance
     */
    public function beginBlock($position)
    {
        return Recorder::begin([
            'position' => $position,
        ]);
    }

    /**
     * Ends recording a block.
     *
     * @return Recorder the [[Recorder]] widget instance
     * @throws InvalidCallException if [[beginBlock()]] and [[endBlock()]] calls are
     * not properly nested in [[\yii\base\Widget::end()]].
     * @see [[beginBlock()]]
     */
    public function endBlock()
    {
        return Recorder::end();
    }

    /**
     * Returns a template.
     * If no id is provided the default template will be loaded.
     *
     * @param int $id optional id of a template.
     * @return TemplateManagerObject
     */
    public function getTemplate($id = 0)
    {
        return $this->templateManager->getItem($id);
    }

    /**
     * Sets the id of the active template. If the provided id is null or 0 (zero)
     * the default template will be registered (but not loaded immediately).
     *
     * @param int|TemplateManagerObject $id an id of a template or a template manager object.
     */
    public function setTemplate($id)
    {
        $this->templateManager->setActive($id);
        $template = $this->getTemplate();
        if (!empty($template->layout)) {
            Yii::$app->layout = $template->layout;
        }
    }

    /**
     * Returns positions used in the provided [theme](yii\base\Theme). If the file "positions.php" does not exist in the
     * root directory of the provided theme an empty array is returned.
     * 
     * A file named "positions.php" can be placed in the root directory of the specified theme. The file must return
     * an array where the keys are position ids and the values are position names.
     * The position ids (the keys) are similar to the ones used in layout files. The names (the values) are used when displaying the
     * positions in the user interface.
     *
     * Example of file content: 
     *
     * ~~~php
     * return [
     *     'position-id' => 'Displayed title',
     *     'mainmenu' => 'Main menu',
     *     'gallery-frontpage' => 'Frontpage gallery',
     * ];
     * ~~~
     *
     * @param string $theme path or alias for a theme.
     * @return array list of positions used in the specified theme.
     */
    public function getThemePositions($theme)
    {
        $file = Yii::getAlias($theme . '/positions.php');
        if (is_file($file)) {
            return require($file);
        } else {
            return [];
        }
    }

    /**
     * Returns positions used in the active theme.
     *
     * @return array list of positions used in the active theme.
     */
    public function getActiveThemePositions()
    {
         // get the current view. If a controller exists use the controller view otherwise
        // use the application view.
        $controller = Yii::$app->controller;
        $view = $controller ? $controller->getView() : Yii::$app->getView();
        if ($view->theme && $view->theme instanceof yii\base\Theme) {
            // find positions available for the current theme
            return $this->getThemePositions($view->theme->basePath);
        } else {
            return [];
        }
    }

    /**
     * Returns positions used in the frontend theme.
     *
     * @return array list of positions used in the frontend theme.
     */
    public function getFrontendThemePositions()
    {
        return $this->getThemePositions($this->frontendTheme);
    }

    /**
     * Returns layouts available in the specified theme.
     * This method looks in the directory "views/layouts" within the provided theme for layout files.
     *
     * @param string $theme path or alias for a theme.
     * @return array list of avalible layouts for the provided theme. The keys are file names (without the ".php" extension)
     * and the values are names of the files with the first letter as uppercase.
     */
    public function getThemeLayouts($theme)
    {
        $folder = Yii::getAlias($theme . '/views/layouts');
        $files = [];
        if (is_dir($folder)) {
            $files = FileHelper::findFiles($folder, ['only' => ['*.php']]);
        }
        $layouts = ['' => Yii::t('big', 'Use default layout')];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $layouts[$name] = ucfirst($name);
        }
        return $layouts;
    }

    /**
     * Returns layouts available in the active theme.
     *
     * @return array list of positions used in the active theme.
     */
    public function getActiveThemeLayouts()
    {
        // get the current view. If a controller exists use the controller view otherwise
        // use the application view.
        $controller = Yii::$app->controller;
        $view = $controller ? $controller->getView() : Yii::$app->getView();
        if ($view->theme && $view->theme instanceof yii\base\Theme) {
            // find positions available for the current theme
            return $this->getThemeLayouts($view->theme->basePath);
        } else {
            return [];
        }
    }

    /**
     * Returns layouts available in the frontend theme.
     *
     * @return array list of positions used in the frontend theme.
     */
    public function getFrontendThemeLayouts()
    {
        return $this->getThemeLayouts($this->frontendTheme);
    }

    /**
     * Returns a string representing the current version of Big
     *
     * @return string the version of Big
     */
    public function getVersion()
    {
        return self::BIG_VERSION;
    }

    /**
     * Returns an array with all core classes used in Big.
     *
     * @return array list of core classes used in Big
     */
    public function getCoreManagers()
    {
        return [
            'urlManager' => ['class' => 'bigbrush\big\core\UrlManager'], // needs to be registered first so other managers can use it
            'menuManager' => ['class' => 'bigbrush\big\core\MenuManager'],
            'blockManager' => ['class' => 'bigbrush\big\core\BlockManager'],
            'categoryManager' => ['class' => 'bigbrush\big\core\CategoryManager'],
            'templateManager' => ['class' => 'bigbrush\big\core\TemplateManager'],
            'extensionManager' => ['class' => 'bigbrush\big\core\ExtensionManager'],
            'pluginManager' => ['class' => 'bigbrush\big\core\PluginManager'],
            'configManager' => ['class' => 'bigbrush\big\core\ConfigManager'],
        ];
    }

    /**
     * Registers a set of managers as definitions in this locator.
     *
     * @param array $managers manager definitions or instances.
     */
    public function setManagers($managers)
    {
        foreach ($this->getCoreManagers() as $id => $manager) {
            if (isset($managers[$id])) {
                $managers[$id] = array_merge($manager, $managers[$id]);
            } else {
                $managers[$id] = $manager;
            }
        }
        $this->setComponents($managers);
    }

    /**
     * Returns the block manager.
     *
     * @return BlockManager
     */
    public function getBlockManager()
    {
        return $this->get('blockManager');
    }

    /**
     * Returns the menu manager.
     *
     * @return MenuManager
     */
    public function getMenuManager()
    {
        return $this->get('menuManager');
    }

    /**
     * Returns the category manager.
     *
     * @return CategoryManager
     */
    public function getCategoryManager()
    {
        return $this->get('categoryManager');
    }

    /**
     * Returns the url manager.
     *
     * @return UrlManager
     */
    public function getUrlManager()
    {
        return $this->get('urlManager');
    }

    /**
     * Returns the template manager.
     *
     * @return TemplateManager
     */
    public function getTemplateManager()
    {
        return $this->get('templateManager');
    }

    /**
     * Returns the extension manager.
     *
     * @return ExtensionManager
     */
    public function getExtensionManager()
    {
        return $this->get('extensionManager');
    }

    /**
     * Returns the plugin manager.
     *
     * @return PluginManager
     */
    public function getPluginManager()
    {
        return $this->get('pluginManager');
    }

    /**
     * Returns the config manager.
     *
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->get('configManager');
    }

    /**
     * Returns a parser instance.
     * A new parser is always created when this method is called.
     *
     * @return Parser
     */
    public function getParser()
    {
        return Yii::createObject(['class' => $this->parser]);
    }
}
