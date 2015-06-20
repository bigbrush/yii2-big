<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use yii\base\InvalidParamException;
use yii\base\Event;
use yii\helpers\ArrayHelper;

/**
 * SearchEvent
 */
class SearchEvent extends Event
{
    /**
     * events
     */
    const EVENT_SEARCH = 'big.search';

    /**
     * @var string the value being searched for.
     */
    public $value;
    /**
     * @var array list of items registered in this event. Refer to [[getDefaultItem()]]
     * to see how each item is setup. 
     */
    public $items = [];
    

    /**
     * Adds a search result to this event. The provided result must be an array which
     * contains the keys "title", "route", "text", "date" and "section".
     *
     * Format:
     * ~~~
     * [
     *     'title' => 'Title of the item',
     *     'route' => ['module/controller/action', 'id' => 'Yii2'],
     *     'text' => 'Text or description of the item',
     *     'date' => 'An important date to the item (could be creation date)'
     *     'section' => 'which section the item belongs to',
     * ]
     * ~~~
     *
     * @param array $item an item to add as a search result
     */
    public function addItem(array $item)
    {
        if (!isset($item['title'], $item['route'], $item['text'], $item['date'], $item['section'])) {
            throw new InvalidParamException("Item added in search event must contain the keys 'title', 'route', 'text', 'date' and 'section'");
        }
        $this->items[] = $item;
    }
}