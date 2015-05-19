<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\Object;
use yii\base\InvalidValueException;
use yii\web\UrlRuleInterface;

/**
 * UrlManager acts as a url manager within Big and as an url rule within the Yii application.
 */
class UrlManager extends Object implements UrlRuleInterface
{
    /**
     * @var boolean defines if this url manager should be used as a url rule in the application
     * url manager. Defaults to true.
     */
    public $enableUrlRule = true;
    /**
     * @var string the URL suffix used for this rule.
     * For example, ".html" can be used so that the URL looks like pointing to a static HTML page.
     * If not, the value of [[yii\web\UrlManager::suffix]] will be used.
     */
    public $suffix;
    /**
     * @var string defines the class name to use when loading url rules. All url
     * rules must be within the same namespace as the main module file (i.e. Module.php)
     */
    public $urlRuleClass = 'UrlRule';
    /**
     * @var array list of rules already loaded. Each rule is indexed by the module ID
     * it is apart of. False will be registered if a module id has no url rule created.
     */
    private $_rules = [];


    /**
     * Initializes the url manager by registering it as an url rule in the application
     * url manager. If will only register it self if [[enableUrlRule]] is true.
     *
     * This method is called when [[Big]] bootstraps.
     */
    public function init()
    {
        if ($this->enableUrlRule) {
            Yii::$app->getUrlManager()->addRules([$this]);
        }
    }

    /**
     * Creates a URL according to the given route and parameters.
     *
     * If the provided route and params mathces a menu item no module url rules are used. In this
     * case the created url will have any parent menu items prepended in the return url.
     *
     * Example:
     * If the provided route and params matches a menu without a parent the menu alias is returned.
     * If the provided route and params matches a menu with a parent the returned url will have aliases
     * of all its parents prepended to the returned url - I.e.: parentmenu/submenu/sub-submenu
     *
     * @param UrlManager $manager the URL manager
     * @param string $route the route. It should not have slashes at the beginning or the end.
     * @param array $params the parameters
     * @return string|boolean the created URL, or false if this rule cannot be used for creating this URL.
     */
    public function createUrl($manager, $route, $params)
    {
        // search for a menu that matches
        $menuManager = Yii::$app->big->menuManager;
        $search = [$route] + $params;
        $menu = $menuManager->search('route', $this->createInternalUrl($search, false), true);
        $url = false;
        if ($menu) {
            if ($menu->getIsDefault()) {
                return '';
            } else {
                $menuManager = Yii::$app->big->menuManager;
                $url = $menu->alias;
                $prepend = '';
                while ($menu = $menuManager->getParent($menu)) {
                    $prepend = $menu->alias.'/';
                }
                $url = $prepend.$url;
            }
        } else {
            // search for an url rule that matches
            foreach (Yii::$app->getModules() as $id => $module) {
                if (($rule = $this->findModuleUrlRule($id, $module)) !== false) {
                    if (($url = $rule->createUrl($manager, $route, $params)) !== false) {
                        break;
                    } 
                }
            }
        }
        if ($url !== false) {
            $url .= ($this->suffix === null ? $manager->suffix : $this->suffix);
        }
        return $url;
    }

    /**
     * Parses the given request and returns the corresponding route and parameters.
     *
     * @param UrlManager $manager the URL manager
     * @param Request $request the request component
     * @return array|boolean the parsing result. The route and the parameters are returned as an array.
     * If false, it means this rule cannot be used to parse this path info.
     */
    public function parseRequest($manager, $request)
    {
        $pathInfo = $request->getPathInfo();
        $suffix = (string) ($this->suffix === null ? $manager->suffix : $this->suffix);
        if ($suffix !== '' && $pathInfo !== '') {
            $n = strlen($suffix);
            if (substr_compare($pathInfo, $suffix, -$n, $n) === 0) {
                $pathInfo = substr($pathInfo, 0, -$n);
                if ($pathInfo === '') {
                    // suffix alone is not allowed
                    return false;
                }
            } else {
                return false;
            }
        }

        // search for a menu that matches the request.
        if (strpos($pathInfo, '/') !== false) {
            $segments = explode('/', $pathInfo);
        } else {
            $segments = [$pathInfo];
        }
        $menuManager = Yii::$app->big->menuManager;
        $menu = $menuManager->search('alias', array_pop($segments), true);
        // if a menu was found set it as active and use it to parse the request.
        if ($menu) {
            $menuManager->setActive($menu);
            $parts = explode('&', $menu->route);
            $route = $parts[0];
            $params = [];
            if (isset($parts[1])) {
                parse_str($parts[1], $params);
            }
            return [$route, $params];
        } elseif (!empty($segments)) {
            // no menu was found by the first segment. Search remaining segments for
            // a matching menu.
            while (!$menu && !empty($segments)) {
                $menu = $menuManager->search('alias', array_pop($segments), true);
            }
            // if a menu was found register it as active
            if ($menu) {
                $menuManager->setActive($menu);
            }
        }
        // no menu found. Search module url rules to find a match.
        $result = false;
        foreach (Yii::$app->getModules() as $id => $module) {
            if (($rule = $this->findModuleUrlRule($id, $module)) !== false) {
                if (($result = $rule->parseRequest($manager, $request)) !== false) {
                    break;
                } 
            }
        }
        return $result;
    }

    /**
     * Creates an internal url.
     * If $dynamicUrl is true the url will be parsed by [[bigbrush\big\core\Parser::parseUrls()]].
     *
     * @param string|array $route use a string to represent a route (e.g. `site/index`),
     * or an array to represent a route with query parameters (e.g. `['site/index', 'param1' => 'value1']`).
     * @param boolean $dynamicUrl if true the url will have "index.php?r" prepended.
     * @return string the internal url
     */
    public function createInternalUrl($route, $dynamicUrl)
    {
        if ($dynamicUrl) {
            // example "index.php?r=". Depends on routeParam in application url manager
            $url = 'index.php?'.Yii::$app->getUrlManager()->routeParam.'=';
        } else {
            $url = '';
        }
        if (is_array($route)) {
            $url .= $route[0];
            unset($route[0]);
            $url .= empty($route) ? '' : '&' . http_build_query($route);
        } else {
            $url .= $route;
        }
        return $url;
    }

    /**
     * Parses an internal url.
     * This method in called by [[bigbrush\big\core\Parser::replaceRoute()]] when changing
     * urls inserted in the editor.
     *
     * @param string $pathInfo the path info to parse.
     * @return array an array to represent a route with query parameters (e.g. `['site/index', 'param1' => 'value1']`).
     */
    public function parseInternalUrl($pathInfo)
    {
        $pathInfo = str_replace('&amp;', '&', $pathInfo);
        if (strpos($pathInfo, 'index.php?') === 0) {
            // remove "index.php?"
            $pathInfo = substr($pathInfo, 10);
        }
        parse_str($pathInfo, $params);
        $manager = Yii::$app->getUrlManager();
        $params[0] = $params[$manager->routeParam];
        unset($params[$manager->routeParam]);
        return $manager->createUrl($params);
    }

    /**
     * Searches for an url rule for the provided module id.
     *
     * @param string $id the id of a module
     * @param array|Object $module an array if the module has not been instantiated and
     * an object if it has.
     * @return UrlRuleInterface|false
     * @throws InvalidValueException
     */
    public function findModuleUrlRule($id, $module)
    {
        if (isset($this->_rules[$id])) {
            return $this->_rules[$id];
        }
        if (is_object($module)) {
            $class = $module::className();
        } else {
            $class = $module['class'];
        }
        // load url rule from same namespace as the main module file
        $class = substr($class, 0, strrpos($class, '\\')+1).$this->urlRuleClass;
        if (class_exists($class)) {
            $rule = Yii::createObject([
                'class' => $class,
            ]);
            if ($rule instanceof UrlRuleInterface) {
                return $this->_rules[$id] = $rule;
            } else {
                throw new InvalidValueException("Url rule '".get_class($rule)."' must implement yii\web\UrlRuleInterface");
            }
        }
        return $this->_rules[$id] = false;
    }
}
