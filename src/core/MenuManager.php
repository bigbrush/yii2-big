<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

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
    public $autoLoad = false;
    /**
     * @var int defines an id for the menu that has the has default menu item reigstered.
     * If this property is not set it will be autoloaded in [[getDefaultMenu()]].
     */
    private $defaultMenuId = 0;


    /**
     * Initializes the manager by autoloading all menus if [[autoLoad]] is enabled.
     * Also sets up trait properties.
     */
    public function init()
    {
        // set properties defined in trait
        $this->tableName = 'menu';
        $this->itemClass = 'bigbrush\big\core\MenuManagerObject';
        $this->modelClass = 'bigbrush\big\models\Menu';

        if ($this->autoLoad) {
            $this->getMenus();
        }
    }

    /**
     * Searches all loaded menu items for an item where the provided
     * property matches the provided value. False is returned if no
     * matching menu item is found.
     *
     * @param string $property the property to compare against.
     * @param string $value the value to compare against.
     * @return MenuManagerObject|false
     */
    public function search($property, $value)
    {
        foreach ($this->_items as $items) {
            foreach ($items as $item) {
                if ($item->$property === $value) {
                    return $item;
                }
            }
        }
        return false;
    }

    /**
     * Returns a list of all menus.
     * This method loads all menus and menu items.
     *
     * @return array list of all menus
     */
    public function getMenus()
    {
        return $this->getRoots();
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
     * Returns the direct parent of the provided menu item.
     * If a menu is provided false is returned.
     *
     * @param MenuManagerObject|bigbrush\big\models\Menu Either a model or a manager object.
     * @return MenuManagerObject|false a menu if the provided menu has a parent. False if not.
     */
    public function getParent($menu)
    {
        if ($menu->lft == 1) {
            return false;
        }
        foreach ($this->_items as $items) {
            foreach ($items as $item) {
                if ($item->tree == $menu->tree && $item->lft < $menu->lft && $item->rgt > $menu->rgt && $menu->depth -1 == $item->depth) {
                    return $item;
                }
            }
        }
        return false;
    }

    /**
     * Returns the menu that has the default menu item.
     *
     * @return array list of menu items
     */
    public function getDefaultMenu()
    {
        if ($this->defaultMenuId) {
            return $this->getItems($this->defaultMenuId);
        } else {
            $tree = $this->find()
                ->select('m1.*')
                ->where([$this->tableAlias.'.is_default' => 1])
                ->leftJoin($this->tableName . ' m1', 'm1.tree = '.$this->tableAlias.'.tree')
                ->orderBy('lft')
                ->all();
            if (empty($tree)) {
                return $this->_roots = []; // flags menus as loaded.
            } else {
                $this->defaultMenuId = $tree[0]['id'];
                $this->createTree($tree);
                return $this->getItems($this->defaultMenuId);
            }
        }
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
                    'section' => 'Menus',
                ]);
            }
        }
    }
}
