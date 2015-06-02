<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\Object;
use yii\base\ErrorException;
use yii\helpers\ArrayHelper;

/**
 * CategoryManager
 */
class CategoryManager extends Object
{
    use NestedSetManagerTrait;

    /**
     * @var array maps module ids to root node ids. The keys are module ids and the values are
     * ids of a tree root node. Root nodes acts as identifier for each tree of categories. Root
     * nodes are the only records which has the property "module" set.
     */
    private $_categories = [];
    /**
     * @var string defines an id for the currently selected root node. This is only set when
     * a full tree is loaded.
     */
    private $_id;


    /**
     * Initializes the manager by setting properties in [[NestedSetManagerTrait]].
     */
    public function init()
    {
        // set properties defined in trait
        $this->tableName = 'category';
        $this->modelClass = 'bigbrush\big\models\Category';
    }

    /**
     * Returns an array ready for drop down lists.
     *
     * @var string $module optional module id to load categories for.
     * @param string $indenter a string that nested elements will be indented by.
     * @return array categories ready for a drop down list.
     */
    public function getDropDownList($module = null, $indenter = '- ')
    {
        if ($module === null) {
            $module = $this->getActiveModuleId();
        }

        if ($indenter === false) {
            return ArrayHelper::map($this->getCategories($module), 'id', 'title');
        }
        
        $categories = [];
        foreach ($this->getCategories($module) as $category) {
            $categories[$category->id] = str_repeat($indenter, $category->depth - 1) . $category->title;
        }
        return $categories;
    }

    /**
     * Returns a category tree for the provided module id. If no module id is provided
     * a category tree for the current module is returned.
     *
     * If no category has been created for the module id a new category tree
     * will be automatically created.
     * 
     * @var string $module optional module id to load categories for.
     * @return array a category tree. Empty array if a category tree is automatically created.
     * @throws ErrorException if a category tree for a module could not be created.
     */
    public function getCategories($module = null)
    {
        if ($module === null) {
            $module = $this->getActiveModuleId();
        }

        if (isset($this->_categories[$module]) || $this->loadCategoryTree($module)) {
            return $this->getItems($this->_categories[$module]);
        } elseif ($this->createRootNode($module)) {
            return [];
        } else {
            throw new ErrorException("Categories for module: '$module' could not be created.");    
        }
    }

    /**
     * Returns a single category.
     *
     * @param int $id an id of a category.
     * @return ManagerObject a category if it exists. False if it does not exist.
     * @throws InvalidParamException if category was not found.
     */
    public function getCategory($id)
    {
        // search in loaded items. Queries the datebase if not already loaded.
        $category = $this->getItem($id);
        foreach ($this->getRoots() as $root) {
            if ($root->tree == $category->tree) {
                $this->_categories[$root->module] = $root->id;
                return $category;
            }
        }
        return false;
    }

    /**
     * Saves a category model. 
     *
     * @param bigbrush\big\models\Category a category model to save. Use [[getModel()]]
     * to create or load a model.
     * @return boolean true if save is successful, false if not.
     */
    public function saveModel(&$model)
    {
        if ($model->load(Yii::$app->getRequest()->post()) && $model->validate()) {
            $parent = $model->parents(1)->one();
            if ($model->getIsNewRecord() || $parent->id != $model->parent_id) {
                if ($model->parent_id) {
                    $parent = $this->getModel($model->parent_id);
                } else {
                    $parent = $this->getModel($this->getRootId());
                }
                return $model->appendTo($parent, false);
            } else {
                return $model->save(false);
            }
        } else {
            return false;
        }
    }

    /**
     * Returns an id of the root node for the current tree.
     * 
     * @return int id of the current root node.
     */
    public function getRootId()
    {
        if ($this->_id !== null) {
            return $this->_id;
        }
        // loads categories for the current module and registers the root id.
        $this->getCategories();
        return $this->_id;
    }

    /**
     * Returns an id of the module in the active controller.
     * 
     * @return string an id of a module.
     */
    public function getActiveModuleId()
    {
        return Yii::$app->controller->module->id;
    }

    /**
     * Loads and creates a category tree based on the provided module.
     *
     * The root node is excluded from the created tree. The root node only
     * acts as identifier for the module using the category tree. The root node
     * id is stored in [[$_id]].
     *
     * @param string $module a module id
     * @return boolean true if tree was loaded, false if not
     */
    public function loadCategoryTree($module)
    {
        $tree = $this->find()
            ->select('m1.*')
            ->where([$this->tableAlias.'.module' => $module])
            ->leftJoin($this->tableName . ' m1', 'm1.tree = '.$this->tableAlias.'.tree')
            ->orderBy('lft')
            ->all();
        if (!empty($tree)) {
            $this->_categories[$module] = $this->_id = $tree[0]['id'];
            $this->createTree($tree);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Creates a root node for a tree.
     *
     * @param string $module a module id
     * @return boolean true if save was successful, false if not.
     */
    public function createRootNode($module)
    {
        $model = $this->getModel();
        $model->setAttributes(['module' => $module, 'title' => $module]);
        if ($model->makeRoot()) {
            $this->_id = $model->id;
            $this->createTree([$model->getAttributes()]);
            return true;
        } else {
            return false;
        }
    }
}
