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
use bigbrush\big\models\Block;

/**
 * BlockManager
 */
class BlockManager extends Object
{
    /**
     * @var array a list of output blocks. The keys are the block positions and the values
     * are arrays with block content.
     * You can call Yii::$app->big->beginBlock() and Yii::$app->big->endBlock() to capture small fragments of a view.
     * They can be later accessed with [[getBlocks()]].
     */
    public $blocks = [];
    /**
     * @var string Defines the class path to use when loading a block.
     */
    public $classPath = 'bigbrush\big\blocks';
    /**
     * @var string Defines the filename to use when loading a block.
     */
    public $blockClass = 'Block';
    /**
     * @var boolean defines whether to search in the current view for blocks.
     * If true then blocks set with Yii::$app->getView()->beginBlock() and Yii::$app->getView()->endBlock()
     * will be searched for blocks. In this case the position should be provided to the beginBlock() method.
     * See [[getBlocks()]] for more information. Defaults to true.
     */
    public $useViewBlocks = true;


    /**
     * Returns all blocks in the provided position
     * If [[useViewBlocks]] is true the current application view will
     * searched for blocks.
     *
     * @param string $position optional position to grab blocks by. If not provided
     * all blocks is returned.
     * @return array list of all registered blocks
     */
    public function getBlocks($position = null)
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
     * Adds a block to the provided position
     *
     * @param string $position the position to add the block to
     * @param string $content the block content
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
     * Registers blocks in this manager from the provided configuration array.
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
        $blocks = $this->find()->where(['or', ['id' => $ids]])->indexBy('id')->all();
        foreach ($positions as $position => $blockIds) {
            foreach ($blockIds as $id) {
                if (isset($blocks[$id])) {
                    $block = $blocks[$id];
                    $model = $this->getModel();
                    // id needs to be assigned specifically
                    $model->id = $block['id'];
                    $model->setAttributes($block);
                    $block = $this->createBlock($id, $model);
                    $this->addBlock($position, $block->run());
                }
            }
        }
    }

    /**
     * Returns all installed blocks
     *
     * @return array
     */
    public function getInstalledBlocks()
    {
        $pattern = Yii::getAlias('@'.str_replace('\\', '/', $this->classPath)).'/*';
        $installed = [];
        foreach (glob($pattern, GLOB_ONLYDIR) as $dir) {
            $dir = basename($dir);
            $installed[$dir] = ucfirst($dir);
        }
        return $installed;
    }

    /**
     * Returns a BlockQuery
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
     * Creates a block from the provided block id
     * The created block is setup to react to the save process of [[big\models\Block]].
     * 
     * @param string|int $id the id of a block to create. If an integer is provided the
     * block will be loaded from the database. If a string is provided the block is considered
     * as a new block. In this case id defines the folder in which the block is located.
     * @param Block|null $model an optional Block model to assign to the block. If a model
     * is provided the first parameter has no effect.
     * @return bigbrush\big\core\Block
     * @throws InvalidParamException
     * @throws InvalidConfigException in [[loadBlock()]]
     */
    public function createBlock($id, $model = null)
    {
        if ($model === null) {
            $model = $this->getModel();
            if (is_numeric($id)) {
                $model = $model->findOne($id);
                if ($model === null) {
                    throw new InvalidParamException("Block with id: $id was not found");
                }
            }
        }
        if ($model->id) {
            $id = $model->name;
        }
        $block = $this->loadBlock($id, $model);
        Event::on(Block::className(), ActiveRecord::EVENT_BEFORE_INSERT, [$block, 'onBeforeSave']);
        Event::on(Block::className(), ActiveRecord::EVENT_BEFORE_UPDATE, [$block, 'onBeforeSave']);
        return $block;
    }

    /**
     * Loads a block extension
     * This method can be customized by setting [[classPath]] and [[blockClass]]
     *
     * @param string $folder the folder of the block. This folder must exist in [[classPath]]
     * @param big\models\Block $model the model to assign to the block
     * @return bigbrush\big\core\Block or one of its subclasses.
     * @throws InvalidConfigException
     */
    public function loadBlock($folder, $model)
    {
        $class = $this->classPath.'\\'.$folder.'\\'.$this->blockClass;
        if (class_exists($class)) {
            return Yii::createObject([
                'class' => $class,
                'model' => $model,
            ]);
        } else {
            throw new InvalidConfigException("The class does not exist: $class. Are classPath and blockClass correct in ".get_class($this)."?");
        }
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
        if ($id) {
            return Block::findOne($id);
        } else {
            return new Block();
        }
    }
}