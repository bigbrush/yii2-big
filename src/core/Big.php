<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\Object;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\base\InvalidCallException;
use yii\base\InvalidParamException;
use yii\helpers\Url;
use yii\web\Application;
use yii\web\View;
use yii\web\Response;
use bigbrush\big\widgets\recorder\Recorder;

/**
 * Big is the core class of the Big framework. It provides an interface for
 * common functionalities and access to the different managers.
 *
 * Big can be used as a stand alone Yii 2 extension but is actually created to provide an
 * application with split frontend and backend ready for shared hosts. Big is also
 * designed to be easy to integrate into any Yii 2 application.
 *
 * Big does not require inheritance from any specific classes (other than Yii classes obviously).
 * Big reacts during runtime by implementing the [[BootstrapInterface]] to plug into
 * a running Yii application.
 *
 * @author Michael Bech <mj@bigbrush-agency.com>
 */
class Big extends Object implements BootstrapInterface
{
    /**
     * version
     */
    const BIG_VERSION = '0.0.6';
    /**
     * scopes
     */
    const SCOPE_FRONTEND = 'frontend';
    const SCOPE_BACKEND = 'backend';

    /**
     * @var string path for the frontend theme layout file. Is needed when identifing
     * the available positions in the frontend theme. This property MUST be set when
     * using [[bigbrush\big\widgets\templateeditor\TemplateEditor]].
     */
    public $webTheme;
    /**
     * @var boolean defines whether to use dynamic content. When this is enabled the [[parser]]
     * will parse the application response.
     *
     * Set this to false when include statements are not used in the layout file. If content
     * created with [[bigbrush\big\widgets\editor\Editor]] is being displayed this property
     * should be true.
     */
    public $enableDynamicContent = true;
    /**
     * @var BlockManager the block manager.
     * Defaults to bigbrush\big\core\BlockManager
     */
    public $blockManager;
    /**
     * @var MenuManager the menu manager.
     * Defaults to bigbrush\big\core\MenuManager
     */
    public $menuManager;
    /**
     * @var CategoryManager the category manager.
     * Defaults to bigbrush\big\core\CategoryManager
     */
    public $categoryManager;
    /**
     * @var UrlManager the url manager.
     * Defaults to bigbrush\big\core\UrlManager
     */
    public $urlManager;
    /**
     * @var Template the current template.
     * Defaults to bigbrush\big\core\Template
     */
    public $template;
    /**
     * @var Parser the layout parser.
     * Defaults to bigbrush\big\core\Parser
     */
    public $parser;
    /**
     * @var string the application scope.
     * This is used to add modules automatically.
     * Also used to set correct base url for the editor and file manager in [[bootstrap()]] method.
     * Defaults to [[SCOPE_FRONTEND]].
     */
    private $_scope;
    /**
     * @var boolean defines that a layout file should be fully rendered regardless of any position checks.
     * Used when layout files are being parsed for available positions.
     * This property is used internally.
     * @see [[getFrontendLayoutFilePositions()]].
     * @see [[getLayoutFilePositions()]].
     * @see [[isPositionActive()]].
     */
    private $_renderFullLayoutFile = false;


    /**
     * Bootstraps Big by initializing important properties. Under "backend" scope
     * the big core module will be registered in the main application. This method also hooks
     * into the main application via the event system to parse layout files.
     * This methods runs after the application is configured.
     *
     * @param yii\base\Application $app the application currently running
     * @see http://www.yiiframework.com/doc-2.0/guide-structure-extensions.html#bootstrapping-classes
     */
    public function bootstrap($app)
    {
        // initialize Big
        $this->initialize();
        
        $scope = $this->getScope();        
        if ($scope === self::SCOPE_BACKEND) {
            // set base url of editor and file manager if scope is "backend"
            $baseUrl = Url::to('@web/../');
            Yii::$container->set('bigbrush\big\widgets\editor\Editor', [
                'baseUrl' => $baseUrl,
            ]);
            Yii::$container->set('bigbrush\big\widgets\filemanager\FileManager', [
                'baseUrl' => $baseUrl,
            ]);
        }

        // set the application default route when scope is "frontend"
        if ($scope === self::SCOPE_FRONTEND) {
            $menu = $this->menuManager->getDefault();
            $this->menuManager->setActive($menu);
            $route = $menu->route;
            if (strpos($route, '&') !== false) {
                list($route, $params) = explode('&', $route, 2);
                parse_str($params, $params);
                Yii::$app->defaultRoute = $route;
                foreach ($params as $key => $value) {
                    $_GET[$key] = $value;
                }
            } else {
                Yii::$app->defaultRoute = $route;
            }
        }

        // hook into the Yii application
        $this->registerEventHandlers($app);
    }

    /**
     * Initializes Big by setting important properties
     * This method is called at the beginning of the boostrapping process
     */
    public function initialize()
    {
        // application scope 
        if ($this->_scope === null) {
            $this->setScope(self::SCOPE_FRONTEND);
        }
        // core classes 
        foreach ($this->getCoreClasses() as $property => $class) {
            if (is_array($this->$property)) {
                $this->$property = Yii::createObject(array_merge(['class' => $class], $this->$property));
            } else {
                $this->$property = Yii::createObject(['class' => $class]);
            }
        }
    }

    /**
     * Hooks into the running Yii application by registering various event handlers.
     * Event handlers for searches in Big are registered here.
     * This method is called at the very end of the boostrapping process
     *
     * @param yii\web\Application $app the application currently running
     */
    public function registerEventHandlers($app)
    {
        $view = $app->getView();
        
        // register the menu manager when searching for content in Big.
        $app->on(SearchEvent::EVENT_SEARCH, [$this->menuManager, 'onSearch']);

        // set the page title (if not set) when a layout starts to render.
        $view->on(View::EVENT_BEGIN_PAGE, function($event) use ($view) {
            $menu = $this->menuManager->getActive();
            if (empty($view->title) && $menu) {
                $view->title = (empty($menu->meta_title)) ? $menu->title : $menu->meta_title;
            }
        });

        if ($this->enableDynamicContent) {
            // register event handler to render blocks right before asset bundles are registered by the view.
            $view->on(View::EVENT_END_BODY, [$this, 'renderBlocks']);
        
            // register event handler that parses the application response
            $app->on(Application::EVENT_AFTER_REQUEST, [$this, 'parseResponse']);
        }
    }

    /**
     * Renders blocks assigned to positions in the active template.
     * This handler is triggered by [[View::endBody()]] right before asset bundles are
     * registered. Blocks needs to be rendered here so any assets used in the blocks
     * will be included by the active application view.
     *
     * @param yii\base\Event $event the event being triggered
     */
    public function renderBlocks($event)
    {
        // if the current request can not be handled no controller exists
        $controller = Yii::$app->controller;
        if ($controller) {
            $layoutFile = $controller->findLayoutFile($controller->getView());
            $positions = $this->getLayoutFilePositions($layoutFile);
            // make sure a template is loaded
            $this->template->load();
            // get blocks for positions used in the template
            $positions = $this->template->getPositions(array_keys($positions));
            $this->blockManager->registerPositions($positions);
        }
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
            $response->data = $this->parser->run($response->data, $this->blockManager->getBlocks());
        }
    }

    /**
     * Triggers a Big search event in the running Yii application. The first parameter defines the event
     * to trigger. The second parameter is a name for the event being triggered. The third parameter
     * defines whether to use dynamic urls in the search results. If dynamic urls are enabled all
     * urls will be converted to a non-seo format. See [[UrlManager::createInternalUrl()]] for
     * information about the url format.
     * Dynamic urls will be parsed by [[Parser::parseUrls()]] which is called by [[parseResponse()]].
     *
     * Widget [[bigbrush\big\widgets\bigsearch\BigSearch]] can be used to make searches in Big.
     * Example:
     * ~~~
     * BigSearch::widget([
     *     'value' => 'The value to search for', // optional
     *     'dynamicUrls' => true // optional - defaults to false
     * ]);
     * ~~~
     *
     * To plug into the search event system in Big add the following to the application configuration file.
     * ~~~
     * return [
     *     'id' => 'APPLICATION ID',
     *     ...
     *     'on big.search' => function($event){
     *         $event->addItem([
     *             'title' => 'The title',
     *             'route' => ['app/page/show', 'id' => 3],
     *             'section' => 'The section',
     *         ]);
     *     },
     *     'components' => [...],
     * ];
     * ~~~
     *
     * @param Event $event an event object. If [[bigbrush\big\widgets\bigsearch\BigSearch]] is used this
     * parameter is a [[SearchEvent]] object.
     * @param string $name the event name to trigger.
     * @param booloean $dynamicUrls if true all urls will have "index.php?r" prepended. See
     * [[UrlManager::createInternalUrl()]] for more information.
     * @return array list of search results.
     * Default format of the returned array is: 
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
     */
    public function search($event, $name, $dynamicUrls = false)
    {
        $event->sender = $this;
        Yii::$app->trigger($name, $event);
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
     * This method can be used in the main layout file to check if a position
     * is active in the current template.
     *
     * Use like the following in a layout file:
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
        if ($this->_renderFullLayoutFile) {
            return true;
        } else {
            return empty($this->getTemplate()->getPosition($position)) === false;
        }
    }

    /**
     * Begins recording a block and automatically assigns it to the provided position.
     * A matching [[endBlock()]] call should be called later.
     *
     * Use this method to register blocks from anywhere within the application.
     * For instance:
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
     * not properly nested in [[yii\base\Widget::end()]].
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
     * @return Template
     */
    public function getTemplate($id = 0)
    {
        return $this->template->load($id);
    }

    /**
     * Sets the id of the active template. If the provided id is null or 0 (zero)
     * the default template will be registered (but not loaded immediately).
     *
     * @param int $id id of a template.
     */
    public function setTemplate($id)
    {
        $this->template->setActive($id);
    }

    /**
     * Returns all positions used in the frontend layout file.
     * All asset bundles will be temporarily disabled while the layout
     * file is rendered. By doing this no js/css files will be injected
     * into the main application.
     *
     * @return array list of all positions found
     * @throws InvalidConfigException if [[webTheme]] is not set or [[webTheme]] is not a file.
     */
    public function getFrontendLayoutFilePositions()
    {
        if ($this->webTheme === null) {
            throw new InvalidConfigException("The property 'webTheme' must be set in class bigbrush\big\core\Big. Please update the application config file.");
        } elseif (!is_file(Yii::getAlias($this->webTheme))) {
            throw new InvalidConfigException("The property 'webTheme' is not a file. Please update the application config file.");
        }
        // flag layout file to be fully parsed
        $this->_renderFullLayoutFile = true;
        // disable asset bundles
        $bundles = $this->disableAssetBundles();
        // create a separate view from the application view to avoid
        // assets being registered by the layout
        $view = new View();
        $content = $view->renderPhpFile(Yii::getAlias($this->webTheme), ['content' => '']);
        // reassign asset bundles
        $this->reassignAssetBundles($bundles);
        // reset flag for layout file
        $this->_renderFullLayoutFile = false;
        // parse positions
        return $this->parser->findPositions($content);
    }

    /**
     * Returns all positions in the provided view file.
     *
     * @param string $viewFile the view file. This can be either an absolute file path or an alias of it.
     * @return array list of positions found in the layout.
     * @throws InvalidParamException in [[View::renderFile()]] if the view file does not exist.
     */
    public function getLayoutFilePositions($viewFile)
    {
        // flag layout file to be fully parsed
        $this->_renderFullLayoutFile = true;
        // disable asset bundles
        $bundles = $this->disableAssetBundles();
        // create a separate view from the application view to avoid
        // assets being registered by the layout
        $view = new View();
        // set the current theme is the temporary view to find the proper layout file
        $view->theme = Yii::$app->controller->getView()->theme;
        $content = $view->renderFile($viewFile, ['content' => '']);
        // reassign asset bundles
        $this->reassignAssetBundles($bundles);
        // reset flag for layout file
        $this->_renderFullLayoutFile = false;
        // parse positions
        return $this->parser->findPositions($content);
    }

    /**
     * Disables asset bundles in the Yii application asset manager.
     * This method is used to temporarily disable asset bundles when
     * finding positions in a application layout file.
     *
     * @return array|false list of registered asset bundles or false if asset bundles
     * is disabled in the Yii application asset manager.
     */
    public function disableAssetBundles()
    {
        $manager = Yii::$app->getAssetManager();
        $bundles = $manager->bundles;
        $manager->bundles = false;
        return $bundles;
    }

    /**
     * Reassigns the provided asset bundles in the Yii application asset manager.
     *
     * @param array|false $bundles list of bundles to reassign. False if asset bundles is disabled.
     * @see [[disableAssetBundles]]
     */
    public function reassignAssetBundles($bundles)
    {
        Yii::$app->getAssetManager()->bundles = $bundles;
    }

    /**
     * Sets the application scope.
     *
     * @return mixed the application scope.
     * @throws InvalidCallException if scope is set after application has been configured.
     */
    public function setScope($scope)
    {
        if ($this->_scope !== null) {
            throw new InvalidCallException("Scope can only be set through application configuration.");
        }
        $this->_scope = $scope;
    }

    /**
     * Returns the application scope.
     *
     * @return mixed the application scope.
     */
    public function getScope()
    {
        return $this->_scope;
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
     * Used internally by [[initialize()]].
     *
     * @return array list of core classes used in Big
     */
    public function getCoreClasses()
    {
        return [
            'menuManager' => 'bigbrush\big\core\MenuManager',
            'blockManager' => 'bigbrush\big\core\BlockManager',
            'categoryManager' => 'bigbrush\big\core\CategoryManager',
            'urlManager' => 'bigbrush\big\core\UrlManager',
            'template' => 'bigbrush\big\core\Template',
            'parser' => 'bigbrush\big\core\Parser',
        ];
    }
}
