<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

/**
 * Interface for managers that ensures a unified public API across managers.
 */
interface ManagerInterface
{
    /**
     * Returns an array of all items used in this manager.
     *
     * @return array an array where each value is a [[ManagerObject]].
     */
    public function getItems();

    /**
     * Returns a single manager object used in this manager.
     *
     * @param int $id an id of a manager object.
     * @return ManagerObject a manger object.
     */
    public function getItem($id);

    /**
     * Creates an item object used in this manager.
     *
     * @param array $data configuration array for the object.
     * @return ManagerObject a manager object.
     */
    public function createObject(array $data);

    /**
     * Return a query ready to query the database used with this manager.
     *
     * @return Query a query object.
     */
    public function find();

    /**
     * Returns a model used with this manager. If id is provided the model will be loaded from the database. Otherwise
     * a new model is created.
     *
     * @param int $id optional id of a database record to load.
     * @return ActiveRecord a manager model.
     * @throws InvalidParamException if id is provided and a model is not found in the database.
     */
    public function getModel($id = 0);
}
