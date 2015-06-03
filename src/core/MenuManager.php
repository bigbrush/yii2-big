<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\InvalidParamException;
use yii\base\Object;

/**
 * MenuManager
 */
class MenuManager extends Object
{
    use NestedSetManagerTrait;

    /**
     * @var boolean whether to load all menus automatically when the manager initializes.
     * If true the [[urlManager]] will not make any additional database calls - see [[urlManager::findMenu()]].
     */
    public $autoLoad = true;
    /**
     * @var boolean indicates whether the menu manager should set a default route in the running application.
     * If true the default menu item will determine the default route.
     * @see [[yii\web\Application::defaultRoute]].
     */
    public $setApplicationDefaultRoute = false;
    /**
     * @var int defines an id for the menu that has the has default menu item registered.
     * If this property is not set it will be autoloaded in [[getDefaultMenu()]].
     */
    private $_default;
    /**
     * @var int an id of the active menu.
     */
    public $_active;


    /**
     * Initializes the manager by autoloading all menus if [[autoLoad]] is enabled.
     * Also sets up trait properties.
     *
     * This method is called when [[Big]] bootstraps.
     */
    public function init()
    {
        // set properties defined in trait if not set by application configuration.
        $this->itemClass = 'bigbrush\big\core\MenuManagerObject';
        if ($this->tableName === null) {
            $this->tableName = '{{%menu}}';
        }
        if ($this->modelClass === null) {
            $this->modelClass = 'bigbrush\big\models\Menu';
        }

        // set the application default route when enabled
        if ($this->setApplicationDefaultRoute) {
            $menu = $this->getDefault();
            $this->setActive($menu);
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

        // load all menu trees when auto load is enabled
        if ($this->autoLoad) {
            $this->getMenus(true);
        }
        
        // register this manager when Big performs a search
        Yii::$app->big->searchHandlers[] = [$this, 'onSearch'];
    }

    /**
     * Searches menu items for an item where the provided property
     * matches the provided value.
     *
     * If [[autoLoad]] is enabled a database call will not be made. If
     * [[autoLoad]] is not enabled a database call will be made if a menu is not found
     * in the currently loaded menu items.
     * False is returned if no matching menu item is found.
     *
     * @param string $property the property to compare against.
     * @param string $value the value to compare against.
     * @param boolean $extended whether to only search in loaded items. If true
     * a database call will be made to determine if the menu item exists.
     * @return MenuManagerObject|false
     */
    public function search($property, $value, $extended = false)
    {
        // search in loaded menu items
        foreach ($this->_items as $items) {
            foreach ($items as $item) {
                if ($item->$property === $value) {
                    return $item;
                }
            }
        }

        // query the database on extended searches
        if (!$this->autoLoad && $extended) {
            $menu = $this->find()->andWhere([$property => $value])->one();
            if ($menu) {
                return $this->createObject($menu);
            }
        }
        return false;
    }

    /**
     * Returns a list of all menus.
     * This method loads all menus and menu items if it is the first method called in the manager
     * or when reload is true.
     *
     * @param boolean $reload indicates whether the whole tree should be reloaded regardless
     * if any trees has been loaded before.
     * @return array list of all menus
     */
    public function getMenus($reload = false)
    {
        return $this->getRoots($reload);
    }

    /**
     * Returns a list of all menus
     *
     * @param int $id the id of a menu or a menu item within the same menu
     * @return array list of all menu items from the menu with the provided id
     * @throws InvalidParamException if id is provided and the menu items could not be found.
     */
    public function getMenuItems($id = 0)
    {
        if ($id) {
            return $this->getItems($id);
        } else {
            return $this->getDefaultMenu();
        }
    }

    /**
     * Returns a menu item with the provided id
     *
     * @param int $id the id of a menu item
     * @return MenuManagerObject
     * @throws InvalidParamException if the menu item was not found
     */
    public function getMenuItem($id)
    {
        return $this->getItem($id);
    }

    /**
     * Returns the default menu item
     *
     * @return MenuManagerObject
     * @throws InvalidParamException if a default menu item has not been set.
     */
    public function getDefault()
    {
        $items = $this->getDefaultMenu();
        if (!empty($items)) {
            foreach ($items as $item) {
                if ($item->is_default) {
                    return $item;
                }
            }
        } else {
            throw new InvalidParamException("No default menu item has been set.");
        }
    }

    /**
     * Returns the menu that has the default menu item.
     *
     * @return array list of menu items
     */
    public function getDefaultMenu()
    {
        if ($this->_default) {
            return $this->getItems($this->_default);
        }
        
        $tree = $this->find()
            ->select('m1.*')
            ->where([$this->tableAlias.'.is_default' => 1])
            ->leftJoin($this->tableName . ' m1', 'm1.tree = '.$this->tableAlias.'.tree')
            ->orderBy('lft')
            ->all();
        if (empty($tree)) {
            return $this->_roots = []; // flags menus as loaded.
        } else {
            $this->_default = $tree[0]['id'];
            $this->createTree($tree);
            return $this->getItems($this->_default);
        }
    }

    /**
     * Sets the active menu item.
     *
     * @param MenuManagerObject|bigbrush\big\models\Menu $menu a menu object to register as active.
     */
    public function setActive($menu)
    {
        $this->_active = $menu->id;
    }

    /**
     * Returns the active menu item.
     *
     * @return MenuManagerObject|false the active menu if set and false if not.
     */
    public function getActive()
    {
        if ($this->_active) {
            return $this->getMenuItem($this->_active);
        }
        return false;
    }

    /**
     * Registers menus when big triggers a search.
     * See [[bigbrush\big\core\Big::search()]] for more information about the search process.
     *
     * @param SearchEvent $event the event being triggered
     */
    public function onSearch($event)
    {
        $menus = $this->find()->select(['title', 'route', 'lft'])->orderBy('tree, lft')->all();
        foreach ($menus as $menu) {
            if ($menu['lft'] != 1) {
                $event->addItem([
                    'title' => $menu['title'],
                    'route' => $menu['route'],
                    'text' => '',
                    'date' => '',
                    'section' => Yii::t('big', 'Menus'),
                ]);
            }
        }
    }
}
