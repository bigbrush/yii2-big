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
 * UrlManager acts as an url manager within Big and as an url rule within the Yii application if [[enableUrlRules]] is true.
 */
class UrlManager extends Object implements UrlRuleInterface
{
    /**
     * @var boolean defines if this url manager should be used as a url rule in the application
     * url manager. Defaults to true.
     */
    public $enableUrlRules = false;
    /**
     * @var string defines the class name to use when loading url rules. All url
     * rules must be within the same namespace as the main module file (i.e. Module.php).
     */
    public $urlRuleClass = 'UrlRule';
    /**
     * @var array list of rules this manager should react to. Each rule is indexed by the module ID
     * it is apart of. If a rule is added with [[setRules()]] the key is determined by the how [[setRules()]] is called.
     * @see setRules()
     * @see registerRules()
     */
    private $_rules = [];


    /**
     * Initializes the url manager by registering it as an url rule in the application
     * url manager. If will only register itself if [[enableUrlRules]] is true.
     * All module url rules are also collected during intialization.
     *
     * This method is called when [[Big]] bootstraps. This manager is the first manager being initialized.
     */
    public function init()
    {
        $this->registerRules($this->_rules);
        if ($this->enableUrlRules) {
            Yii::$app->getUrlManager()->addRules([$this]);
            foreach (Yii::$app->getModules() as $id => $module) {
                $this->registerModule($id, $module);
            }
        }
    }

    /**
     * Registers the provided array of url rules.
     *
     * Note that if you add rules after the UrlManager object is created, make sure
     * you populate the array with rule objects instead of rule configurations.
     *
     * @param mixed $rules the following value can be provided
     *   - a string representing a class name
     *   - an array of rule objects
     *   - an array of strings representing class names
     *   - an array of arrays where each array is a url rule configuration object
     */
    public function setRules($rules)
    {
        if (!is_array($rules)) {
            $rules = [$rules];
        }
        $this->registerRules($rules);
    }

    /**
     * Returns all registered url rules.
     * All url rules are ensured to be instantiated objects before being returned. This is done so dynamically added
     * url rules will be creted if a string is registered.
     *
     * @return array url rules added to the url manager.
     */
    public function getRules()
    {
        return $this->_rules;
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
        $menu = $menuManager->search('route', $this->createInternalUrl($search, false));
        $url = false;
        if ($menu) {
            if ($menu->getIsDefault()) {
                return '';
            } else {
                $url = $menu->getQuery();
            }
        } else {
            foreach ($this->getRules() as $rule) {
                if (($url = $rule->createUrl($manager, $route, $params)) !== false) {
                    break;
                }
            }
        }
        if ($url !== false && $manager->suffix !== null) {
            $url .= $manager->suffix;
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
        $suffix = (string) $manager->suffix;
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
        $menu = $menuManager->search('alias', array_pop($segments));
        // if a menu was found set it as active and use it to parse the request.
        if ($menu) {
            $menuManager->setActive($menu);
            $params = $this->parseInternalUrl($menu->route);
            $route = $params[0];
            unset($params[0]);
            return [$route, $params];
        } elseif (!empty($segments)) {
            // no menu was found by the first segment. Search remaining segments for
            // a matching menu.
            while (!$menu && !empty($segments)) {
                $menu = $menuManager->search('alias', array_pop($segments));
            }
            // if a menu was found register it as active
            if ($menu) {
                $menuManager->setActive($menu);
            }
        }
        // no menu found. Search module url rules to find a match.
        $result = false;
        foreach ($this->getRules() as $rule) {
            if (($result = $rule->parseRequest($manager, $request)) !== false) {
                break;
            }
        }
        return $result;
    }

    /**
     * Creates an internal url. If a dynamic url is created the string "index.php?r=" is prepended.
     *
     * The second parameter, $dynamicUrl, should be true when the url is used in content being saved to the
     * database. It will then be parsed by [[bigbrush\big\core\Parser::parseUrls()]] when the page is being rendered.
     *
     * @param string|array $route use a string to represent a route (e.g. `site/index`),
     * or an array to represent a route with query parameters (e.g. `['site/index', 'param1' => 'value1']`).
     * @param boolean $dynamicUrl if true the url will have "index.php?r" prepended.
     * @return string the internal url
     * @see parseInternalUrl()
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
     * Converts a url created with [[createInternalUrl()]] into an array with route params.
     * 
     * For instance:
     * ~~~php
     * // ['pages/page/show', 'id' => '28', 'alias' => 'something']
     * $urlManager->parseInternalUrl('pages/page/show&id=28&alias=something');
     * ~~~
     *
     * This method in called by [[bigbrush\big\core\Parser::replaceRoute()]] when changing
     * urls inserted in the editor.
     *
     * @param string $pathInfo the path info to parse.
     * @return array an array to represent a route with query parameters (e.g. `['site/index', 'param1' => 'value1']`).
     */
    public function parseInternalUrl($pathInfo)
    {
        if (strpos($pathInfo, 'index.php?') === 0) {
            // dynamic url
            $pathInfo = substr($pathInfo, 10); // remove "index.php?"
            parse_str($pathInfo, $params);
            $manager = Yii::$app->getUrlManager();
            $params[0] = $params[$manager->routeParam];
            unset($params[$manager->routeParam]);
        } elseif (strpos($pathInfo, '&') !== false) {
            // non-dynamic url with route params 
            list($route, $params) = explode('&', $pathInfo, 2);
            parse_str($params, $params);
            $params[0] = $route;
        } else {
            // non-dynamic url without route params 
            $params = [$pathInfo];
        }
        return $params;
    }

    /**
     * Registers the provided rules. 
     *
     * @param array $rules an array of rule objects or rule configurations.
     * @throws InvalidValueException if one of the provided rules doesn't implement UrlRuleInterface.
     */
    public function registerRules(array $rules)
    {
        foreach ($rules as $i => $rule) {
            if (is_string($rule)) {
                $rule = Yii::createObject(['class' => $rule]);
            } elseif (is_array($rule)) {
                $rule = Yii::createObject($rule);
            }
            if (!$rule instanceof UrlRuleInterface) {
                throw new InvalidValueException("Url rule '".get_class($rule)."' must implement yii\web\UrlRuleInterface");
            }
            $this->_rules[$i] = $rule;
        }
    }

    /**
     * Searches for an url rule for the provided module id and registers it in this manager as an url rule.
     *
     * @param string $id the id of a module.
     * @param array|yii\base\Object $module an array if the module has not been instantiated and
     * an object if it has.
     * @throws InvalidValueException if an identified url rule doesn't implement [[UrlRuleInterface]].
     */
    public function registerModule($id, $module)
    {
        if (is_object($module)) {
            $class = $module::className();
        } else {
            $class = $module['class'];
        }
        // load url rule from same namespace as the main module file
        $class = substr($class, 0, strrpos($class, '\\') + 1) . $this->urlRuleClass;
        if (class_exists($class) === false) {
            return false;
        }
        // create and register url rule
        $this->registerRules([$class]);
    }
}
