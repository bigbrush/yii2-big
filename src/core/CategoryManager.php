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
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;

/**
 * CategoryManager
 */
class CategoryManager extends Object implements ManagerInterface
{
    use NestedSetManagerTrait {
        getItems as _getItems;
        getItem as _getItem;
    }

    /**
     * @var array maps module ids to root node ids. The keys are module ids and the values are
     * ids of a tree root node. Root nodes acts as identifier for each tree of categories. Root
     * nodes are the only records which has the property "module" set.
     */
    private $_mapper = [];


    /**
     * Initializes the manager by setting properties in [[NestedSetManagerTrait]].
     */
    public function init()
    {
        // set properties defined in trait if not set by application configuration.
        if ($this->modelClass === null) {
            $this->modelClass = 'bigbrush\big\models\Category';
        }
    }

    /**
     * Returns an array ready for drop down lists for the provided module.
     *
     * Note this method uses the property "title" if an item.
     *
     * @param string $module a module id to load categories for.
     * @param string $unselected optional text to use as a state of "unselected".
     * @param string $indenter a string that nested elements will be indented by.
     * @return array categories ready for a drop down list.
     */
    public function getDropDownList($module, $unselected = null, $indenter = '- ')
    {
        $categories = $unselected ? [$unselected] : [];
        if (!$indenter) {
            return $categories + ArrayHelper::map($this->getItems($module), 'id', 'title');
        }
        $depthAttribute = $this->getDatabaseColumnName('depth');
        foreach ($this->getItems($module) as $category) {
            $categories[$category->id] = str_repeat($indenter, $category->$depthAttribute - 1) . $category->title;
        }
        return $categories;
    }

    /**
     * Returns a category tree for the provided module id.
     *
     * If no category has been created for the module id a new category tree
     * will be automatically created.
     * 
     * @param string $id a module id to load categories for.
     * @return array a category tree. Empty array if a category tree is automatically created.
     * @throws ErrorException in [[createRootNode()]].
     */
    public function getItems($id = null)
    {
        if ($id === null) {
            throw new InvalidParamException("Id must be provided when loading categories.");
        } elseif (isset($this->_mapper[$id]) || $this->loadCategoryTree($id)) {
            return $this->_getItems($this->_mapper[$id]);
        } else {
            $this->createRootNode($id);
            return [];
        }
    }

    /**
     * Returns a single category.
     *
     * @param int $id an id of a category.
     * @return ManagerObject a category if it exists. False if it does not exist.
     * @throws InvalidParamException if category was not found.
     */
    public function getItem($id)
    {
        $treeAttribute = $this->getDatabaseColumnName('tree');
        // search in loaded items. Queries the database if not already loaded.
        $category = $this->_getItem($id);
        foreach ($this->getRoots() as $root) {
            if ($root->$treeAttribute == $category->$treeAttribute) {
                $this->_mapper[$root->module] = $root->id;
                return $category;
            }
        }
        return false;
    }

    /**
     * Saves a category model.
     *
     * Note this method uses the property "parent_id" which should be populated with an id of
     * a parent item (if any).
     *
     * @param string $id a module id the provided category belongs to.
     * @param bigbrush\big\models\Category a category model to save. Use [[getModel()]]
     * to create or load a model.
     * @return boolean true if save is successful, false if not.
     * @throws ErrorException in [[createRootNode()]].
     */
    public function saveModel($id, &$model)
    {
        if ($model->load(Yii::$app->getRequest()->post()) && $model->validate()) {
            $parent = $model->parents(1)->one();
            $root = $model->find()->where(['module' => $id])->one();
            
            if (!$model->parent_id) {
                if (!$parent || $parent->id !== $root->id) {
                    return $model->appendTo($root, false);
                } else {
                    return $model->save(false);
                }
            } else {
                if (!$parent || $parent->id != $model->parent_id) {
                    $parent = $this->getModel($model->parent_id);
                    return $model->appendTo($parent, false);
                } else {
                    return $model->save(false);
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Loads and creates a category tree based on the provided module.
     *
     * The root node is excluded from the created tree. The root node only
     * acts as identifier for the module using the category tree.
     *
     * @param string $module a module id
     * @return boolean true if tree was loaded, false if not
     */
    public function loadCategoryTree($module)
    {
        if ($this->loadTree('module', $module)) {
            $root = $this->searchRoots('module', $module);
            $this->_mapper[$module] = $root->id;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Creates a root node for a category tree.
     *
     * @param string $id a module id to create the root node for.
     * @return bigbrush\big\models\Category the created root node. Return model is defined by [[modelClass]].
     * @throws ErrorException if a root node for the provided id could not be created.
     */
    public function createRootNode($id)
    {
        $model = $this->getModel();
        $model->setAttributes(['module' => $id, 'title' => $id]);
        if ($model->makeRoot()) {
            $this->createTree([$model->getAttributes()]);
            return $model;
        } else {
            throw new ErrorException("Categories for module: '$id' could not be created.");
        }
    }
}
