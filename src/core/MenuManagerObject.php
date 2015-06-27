<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;

/**
 * MenuManagerObject
 *
 * @property integer $id
 * @property string $title
 * @property string $alias
 * @property string $route
 * @property int $state
 * @property int $tree
 * @property int $lft
 * @property int $rgt
 * @property int $depth
 * @property int $is_default
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 */
class MenuManagerObject extends ManagerObject
{
    /**
     * @var string the url for this menu item
     */
    private $_url;
    /**
     * @var string the query string for this menu item
     */
    private $_query;


    /**
     * Returns the url of this menu item.
     * If a suffix is registered in the application url manager it will be appended when the url is internal.
     *
     * @return string the url.
     */
    public function getUrl()
    {
        if ($this->_url === null) {
            if ($this->getIsDefault()) {
                $this->_url = '';
            } else {
                $this->_url = $this->getQuery();
                // only append suffix on internal urls
                if ($this->route !== '#' && strpos($this->route, 'http://') !== 0 && strpos($this->route, 'www') !== 0) {
                    $this->_url .= Yii::$app->getUrlManager()->suffix;
                }
            }
        }
        return $this->_url;
    }

    /**
     * Returns the query string of this menu item.
     *
     * @return string the query string.
     */
    public function getQuery()
    {
        if ($this->_query === null) {
            if ($this->route === '#' || strpos($this->route, 'http://') === 0) {
                $this->_query = $this->route;
            } elseif (strpos($this->route, 'www') === 0) {
                $this->_query = 'http://' . $this->route;
            } else {
                $manager = Yii::$app->big->menuManager;
                $menu = $this;
                $query = '';
                while ($menu = $manager->getParent($menu)) {
                    $query .= $menu->alias . '/';
                }
                $this->_query = $query . $this->alias;
            }
        }
        return $this->_query;
    }

    /**
     * Returns a boolean indicating if this is the default menu item
     *
     * @return boolean true if this is the default menu item, false if it is not.
     */
    public function getIsDefault()
    {
        return $this->is_default === '1';
    }

    /**
     * Returns a boolean indicating whether this menu is enabled.
     *
     * @return boolean true if this menu is enabled and false if it is not.
     */
    public function getIsEnabled()
    {
        return $this->state === '1';
    }
}
