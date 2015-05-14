<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\InvalidParamException;
use yii\db\Query;

/**
 * NestedSetManagerTrait
 */
trait NestedSetManagerTrait
{
    /**
     * @var string name of a database table to load trees from.
     */
    public $tableName;
    /**
     * @var string represents the model class when creating/editing an item.
     */
    public $modelClass;
    /**
     * @var string the alias used when querying the database table.
     */
    public $tableAlias = 'm';
    /**
     * @var string represents the class when creating an item.
     */
    public $itemClass = 'bigbrush\big\core\ManagerObject';
    /**
     * @var array list with all loaded roots indexed by the id of each root.
     * Format of this array: [
     *     'ROOT ID' => Item Object,
     *     'ROOT ID' => Item Object,
     *     ...
     * ]
     */
    private $_roots;
    /**
     * @var array list with all loaded items indexed by the id of the item.
     * Format of this array: [
     *     'ROOT ID' => [
     *          'ITEM ID' => Item Object,
     *          'ITEM ID' => Item Object,
     *           ...
     *      ],
     *     ...
     * ]
     */
    private $_items = [];


    /**
     * Returns all created root items. The whole tree is loaded from the database
     * if this is the first method called in this trait.
     *
     * @param boolean $reload indicates whether the whole tree should be reloaded regardless
     * if any trees has been loaded before.
     * @return array list of roots
     */
    public function getRoots($reload = false)
    {
        if ($this->_roots === null || $reload) {
            $this->_roots = []; // flag tree as loaded.
            $this->createTree($this->find()->orderBy($this->tableAlias.'.tree, '.$this->tableAlias.'.lft')->all());
        }
        return $this->_roots;
    }

    /**
     * Returns a single root item with the provided id.
     *
     * @param int $id the id of a root item
     * @return mixed a root object
     * @throws InvalidParamException if item was not found
     */
    public function getRoot($id)
    {
        if (isset($this->_roots[$id])) {
            return $this->_roots[$id];
        } elseif ($this->loadTree($id)) {
            return $this->_roots[$id];
        } else {
            throw new InvalidParamException("Root with ID: '$id' has not been created in table: '$this->tableName'.");
        }
    }

    /**
     * Returns a tree of items in the provided root id.
     *
     * @param int $id the root id a tree to find
     * @return array list of items
     * @throws InvalidParamException if items was not found
     */
    public function getItems($id)
    {
        if (isset($this->_items[$id])) {
            return $this->_items[$id];
        } elseif ($this->loadTree($id)) {
            return $this->_items[$id];
        } else {
            throw new InvalidParamException("Item with ID: '$id' has not been created in table: '$this->tableName'.");
        }
    }

    /**
     * Returns a single item.
     *
     * @param int $id the id of an item to find
     * @return mixed|false an item if found, otherwise false.
     * @throws InvalidParamException if item was not found
     */
    public function getItem($id)
    {
        if ($item = $this->searchItems($id)) {
            return $item;
        } elseif ($this->loadTree($id)) {
            return $this->searchItems($id);
        } else {
            throw new InvalidParamException("Item with ID: '$id' has not been created in table: '$this->tableName'.");
        }
    }

    /**
     * Searches all items and returns an item that matches the provided id.
     * Note that this method only searches in already loaded items.
     *
     * @param int $id the of an item to find
     * @return ManagerObject|false an object if found, otherwise false.
     */
    public function searchItems($id)
    {
        foreach ($this->_items as $items) {
            if (array_key_exists($id, $items)) {
                return $items[$id];
            }
        }
        return false;
    }

    /**
     * Returns the direct parent of the provided object.
     * If a root object or a new ActiveRecord is provided false is returned.
     *
     * @param ManagerObject|yii\db\ActiveRecord $object either an ActiveRecord or a manager object.
     * @return ManagerObject|false a manager object if the provided object has a parent. False if not.
     */
    public function getParent($object)
    {
        if ($object->lft == 1 || $object->id == 0) {
            return false;
        }
        foreach ($this->_items as $items) {
            foreach ($items as $item) {
                if ($item->tree == $object->tree
                && $item->lft < $object->lft
                && $item->rgt > $object->rgt
                && $item->depth == $object->depth -1) {
                    return $item;
                }
            }
        }
        return false;
    }

    /**
     * Loads and creates a tree based on the provided id.
     * The id can be of a root or an item.
     *
     * @param int $id the id of a root/item
     * @return boolean true if tree was loaded, false if not
     */
    public function loadTree($id)
    {
        $tree = $this->find()
            ->select('m1.*')
            ->where([$this->tableAlias.'.id' => $id])
            ->leftJoin($this->tableName . ' m1', 'm1.tree = '.$this->tableAlias.'.tree')
            ->orderBy('lft')
            ->all();
        $this->createTree($tree);
        return empty($tree) === false;
    }

    /**
     * Creates a tree based on the provided items. The first element of the items
     * must be a root item (lft == 1).
     * If a root is created it is registered in [[_roots]] and if an item
     * is created it is registered in [[_items]].
     *
     * @param array $items list of items to create a from
     * @throws InvalidParamException if a root node is not the first element of the provided array.
     */
    public function createTree($items)
    {
        $rootId = null;
        foreach ($items as $item) {
            $item = $this->createObject($item);
            if ($item->lft == 1) {
                $rootId = $item->id;
                $this->_roots[$rootId] = $item;
                $this->_items[$rootId] = [];
            } elseif ($rootId) {
                $this->_items[$rootId][$item->id] = $item;
            } else {
                throw new InvalidParamException("The first element of the provided array must be a root node.");
            }
        }
    }

    /**
     * Creates an item object used when creating trees.
     *
     * @param array $data configuration array for the object
     * @return ManagerObject or a subclass
     * @see [[createTree()]]
     */
    public function createObject(array $data)
    {
        return Yii::createObject([
            'class' => $this->itemClass
        ], [$data]);
    }

    /**
     * Return a query ready for the [[tableName]] database table. The table
     * is aliased according to [[tableAlias]].
     *
     * @return Query
     */
    public function find()
    {
        $query = new Query();
        $query->from($this->tableName.' '.$this->tableAlias);
        return $query;
    }

    /**
     * Returns model used in the current manager. If id is provided the model will be loaded from the database.
     *
     * @param int $id optional id of a database record to load
     * @return mixed|null 
     * @throws InvalidParamException if model was not found in the database.
     */
    public function getModel($id = 0)
    {
        $model = Yii::createObject(['class' => $this->modelClass]);
        if (!$id) {
            return $model;
        } elseif ($model = $model->findOne($id)) {
        	return $model;
        } else {
            throw new InvalidParamException("Model with id: '$id' not found.");
        }
    }
}