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
 */
class MenuManagerObject extends ManagerObject
{
    /**
     * @var string the url for this menu item
     */
    private $_url;


    /**
     * Returns the url of this menu item.
     *
     * @return string the url
     */
    public function getUrl()
    {
        if ($this->_url === null) {
            if ($this->route === '#') {
                $this->_url = $this->route;
            } elseif (strpos($this->route, '&') !== false) {
                list($route, $params) = explode('&', $this->route, 2);
                parse_str($params, $params);
                $params[0] = $route;
                $this->_url = Yii::$app->getUrlManager()->createUrl($params);
            } else {
                $this->_url = Yii::$app->getUrlManager()->createUrl($this->route);
            }
        }
        return $this->_url;
    }

    /**
     * Returns a boolean indicating if this is the default menu item
     *
     * @return boolean true if this is the default menu item, false if it not.
     */
    public function getIsDefault()
    {
        return (bool) $this->is_default;
    }
}