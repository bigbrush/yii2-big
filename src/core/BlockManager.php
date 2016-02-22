<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\Object;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\db\Query;
use yii\db\ActiveRecord;

/**
 * BlockManager handles all block related tasks in Big.
 *
 * This manager mainly works behind the scene but can be accessed like any other manager.
 * A block can be set with code like so:
 *
 * ~~~php
 * Yii::$app->big->blockManager->addBlock('POSITION NAME', '<p>My block content</p>');
 * ~~~
 *
 */
class BlockManager extends Object implements ManagerInterface
{
    /**
     * @var string represents the model class when creating/editing an item.
     */
    public $modelClass = 'bigbrush\big\models\Block';
    /**
     * @var array a list of output blocks. The keys are positions the blocks are assigned to and the values
     * are arrays with block content.
     * @see [[Big::beginBlock()]]
     * @see [[addBlock()]]
     * Blocks added here can later be accessed with [[getRegisteredBlocks()]].
     */
    public $blocks = [];
    /**
     * @var boolean defines whether to search in the current view for blocks.
     * If true then blocks set with Yii::$app->getView()->beginBlock() and Yii::$app->getView()->endBlock()
     * will be searched for blocks. In this case the position should be provided to the beginBlock() method.
     * See [[getBlocks()]] for more information. Defaults to true.
     */
    public $useViewBlocks = true;


    /**
     * Adds a block to the provided position.
     *
     * @param string $position the position to add the block to.
     * @param string $content the block content.
     */
    public function addBlock($position, $content)
    {
        if(isset($this->blocks[$position])) {
            $this->blocks[$position][] = $content;
        } else {
            $this->blocks[$position] = [$content];
        }
    }

    /**
     * Returns a [[Block]] based on the provided id.
     *
     * @param int an id of a block.
     * @return Block a block instance.
     */
    public function getItem($id)
    {
        $model = $this->getModel($id);
        return $this->createObject([
            'class' => $model->namespace,
            'model' => $model,
        ]);
    }

    /**
     * Returns all created blocks.
     *
     * @return array an array of [[Blocks]].
     */
    public function getItems()
    {
        $blocks = [];
        foreach ($this->find()->all() as $data) {
            $blocks[] = $this->createBlockFromData($data);
        }
        return $blocks;
    }

    /**
     * Creates a new block based on the provided extension id.
     *
     * @param int $id an id of an [[bigbrush\big\models\Extension]] model.
     */
    public function createNewBlock($id)
    {
        $extension = Yii::$app->big->extensionManager->getItem($id);
        $block = $this->createObject([
            'class' => $extension->namespace,
            'model' => $this->getModel(),
        ]);
        $block->model->registerExtension($extension);
        $block->model->show_title = 1;
        $block->model->state = 1;
        return $block;
    }

    /**
     * Registers blocks in this manager from the provided configuration array. Only active blocks are registered.
     * All blocks are rendered when being assigned to each position.
     *
     * @param array $positions list of positions. The keys are position names and the values are
     * arrays of block ids registered to each position.
     */
    public function registerPositions(array $positions)
    {
        $ids = [];
        foreach ($positions as $position => $blockIds) {
            $ids = array_merge($ids, $blockIds);
        }
        $blocks = $this->find()->where(['or', ['id' => $ids]])->andWhere(['state' => \bigbrush\big\models\Block::STATE_ACTIVE])->indexBy('id')->all();
        foreach ($positions as $position => $blockIds) {
            foreach ($blockIds as $id) {
                if (isset($blocks[$id])) {
                    $block = $this->createBlockFromData($blocks[$id]);
                    $this->addBlock($position, $block->run());
                }
            }
        }
    }

    /**
     * Returns all blocks in the provided position.
     * If [[useViewBlocks]] is true the current application view will
     * searched for blocks.
     *
     * @param string $position optional position to grab blocks by. If not provided
     * all blocks is returned.
     * @return array list of all registered blocks.
     */
    public function getRegisteredBlocks($position = null)
    {
        if ($position === null) {
            return $this->blocks;
        }
        $blocks = [];
        if (isset($this->blocks[$position])) {
            $blocks = $this->blocks[$position]; // provides array
        }
        $view = Yii::$app->getView();
        if ($this->useViewBlocks && isset($view->blocks[$position])) {
            $blocks[] = $view->blocks[$position]; // provides string
        }
        return $blocks;
    }

    /**
     * Creates a block of the provided class with the provided model assigned.
     *
     * @param string $class a fully qualified class name without the leading backslash.
     * @param ActiveRecord a block model.
     * @return Block the created block.
     */
    public function createObject(array $data)
    {
        $block = Yii::createObject($data);
        $model = $block->model;
        Event::on($model::className(), ActiveRecord::EVENT_BEFORE_INSERT, [$block, 'onBeforeSave']);
        Event::on($model::className(), ActiveRecord::EVENT_BEFORE_UPDATE, [$block, 'onBeforeSave']);
        return $block;
    }

    /**
     * Configures a [[bigbrush\big\models\Block]] model with the provided data.
     *
     * @param array $data an array of block data.
     */
    public function createBlockFromData($data)
    {
        $model = $this->getModel();
        // id needs to be assigned specifically
        $model->id = $data['id'];
        $model->setAttributes($data);
        return $this->createObject([
            'class' => $model->namespace,
            'model' => $model,
        ]);
    }

    /**
     * Returns all installed blocks. If true is provided only active blocks are returned. If false is provided only
     * inactive blocks are returned. If null is provided all blocks are returned.
     *
     * @param boolean $state indicates whether installed blocks should be loaded by a particular state.
     * @return array list of installed blocks. The keys are extension ids and the values are extension names.
     */
    public function getInstalledBlocks($state = null)
    {
        $installed = [];
        $extensions = Yii::$app->big->extensionManager->getBlocks($state);
        foreach ($extensions as $extension) {
            $installed[$extension->id] = $extension->name;
        }
        return $installed;
    }

    /**
     * Returns a BlockQuery.
     *
     * @return BlockQuery
     */
    public function find()
    {
        $query = new Query();
        $query->from($this->getModel()->tableName());
    	return $query;
    }

    /**
     * Returns a [[Block]] model.
     * If id is provided the model will be loaded from the database otherwise
     * a new model is created.
     *
     * @param int $id optional id of a model
     * @return Block|null a [[Block]] model. If id is provided and the database record doesn't
     * exist null is returned.
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
