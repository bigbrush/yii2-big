<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\BaseObject;
use yii\base\InvalidCallException;
use yii\base\InvalidParamException;
use yii\db\Query;
use bigbrush\big\models\Extension;

/**
 * ExtensionManager handles installation and management of extensions. Currently only block
 * extension exists within Big.
 */
class ExtensionManager extends BaseObject implements ManagerInterface
{
    /**
     * @var string represents the model class when creating/editing an item.
     */
    public $modelClass = 'bigbrush\big\models\Extension';


    /**
     * Returns installed extensions of type block.
     *
     * @param boolean|int $state optional state to load blocks by. Can be "0"/false (inactive) or "1"/true (active).
     * Defaults to null meaning load all installed blocks.
     * @return array list of extensions models of type block.
     */
    public function getBlocks($state = null)
    {
        return $this->getItems(Extension::TYPE_BLOCK, $state);
    }

    /**
     * Returns installed extension by the provided type. If type is not provided
     * all installed extensions are returned.
     *
     * @param string $type optional type of extensions to load.
     * @param boolean|int $state optional state to load extensions by. Can be "0"/false (inactive) or "1"/true (active).
     * Defaults to null meaning load all installed extensions.
     * @return array list of extension models.
     */
    public function getItems($type = null, $state = null)
    {
        $query = $this->getModel()->find()->orderBy('name');
        if ($type !== null) {
            $query->where(['type' => $type]);
        }
        if ($state !== null) {
            $query->where(['state' => $state]);
        }
        return $query->all();
    }

    /**
     * Returns a single extension model.
     *
     * @param int $id an id of a extension.
     * @return bigbrush\big\models\Extension an extension model.
     */
    public function getItem($id)
    {
        return $this->getModel($id);
    }

    /**
     * Returns a [[Query]] ready to query the database table for extensions.
     *
     * @return Query
     */
    public function find()
    {
        $query = new Query();
        $query->from($this->getModel()->tableName());
        return $query;
    }

    /**
     * Not implemented in extension manager.
     */
    public function createObject(array $data)
    {
        throw new InvalidCallException("ExtensionManager::createObject() is not implemented.");
    }

    /**
     * Returns an array of available extension types.
     *
     * Format of returned array:
     *
     * ~~~php
     * [
     *     'blocks' => 'Block',
     *     'TYPE ID' => 'TRANSLATED NAME OF EXTENSION TYPE',
     *     ...
     * ]
     * ~~~
     *
     * @return array list of extension types where the keys are the type id and the values are a translated names.
     * @see [[bigbrush\big\models\Extension::getTypeOptions()]].
     */
    public function getExtensionTypes()
    {
    	return $this->getModel()->getTypeOptions();
    }

    /**
     * Returns a model used in this manager. If id is provided the model will be loaded from the database.
     *
     * @param int $id optional id of a database record to load.
     * @return a model of class [[modelClass]].
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
