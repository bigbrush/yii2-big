<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\widgets\templateeditor;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\web\View;
use bigbrush\big\models\Template;
use bigbrush\big\widgets\templateeditor\assets\TemplateEditorAsset;

/**
 * TemplateEditor
 * 
 * Use this widget to include the template UI in your module.
 * This widget must be used within a form.
 */
class TemplateEditor extends Widget
{
    /**
     * @var bigbrush\big\models\Template the template model. Use [[getModel()]] to load a model.
     */
    public $model;
    /**
     * @var array list of all blocks available to assign to positions. The keys are block ids
     * and the value are Blocks.
     */
    public $blocks;
    /**
     * @var int the number of columns in a row for available positions. Used with the Bootstrap grid system.
     * Must equal a whole number when divided by 12.
     */
    public $columns = 3;
    /**
     * @var bigbrush\big\core\Template a template used to parse theme positions with blocks assigned to the template.
     * Is configured in [[init()]].
     */
    private $_template;


    /**
     * Saves a template
     *
     * @param big\models\Template $model a Template model to save.
     * @return boolean true if save was successful, false otherwise.
     */
    public static function save(&$model)
    {
        if ($model->load(Yii::$app->getRequest()->post())) {
            return $model->save();
        }
        return false;
    }

    /**
     * Returns a template model.
     * If no id is provided the default template model will be loaded.
     * If 0 (zero) is provided a new model will be created.
     * 
     * @param int $id optional id of a template record
     * @return bigbrush\big\models\Template
     */
    public static function getModel($id = 0)
    {
        return Yii::$app->big->templateManager->getModel($id);
    }

    /**
     * Renders all available blocks
     * This is triggered by the begin() method of the widget
     */
    public function init()
    {
        if ($this->model === null) {
            throw new InvalidConfigException("The property 'model' must be set as an instance of bigbrush\big\models\Template.");
        }

        if ($this->blocks === null) {
            $this->blocks = Yii::$app->big->blockManager->find()->indexBy('id')->all();
        }

        $this->_template = Yii::$app->big->templateManager->configure($this->model->getAttributes());
    }

    /**
     * Renders all positions and assigned blocks to each position.
     * This is triggered by the end() method of the widget
     *
     * @return string the result of widget execution to be outputted.
     */
    public function run()
    {
        $availableBlocks = $this->removeAssignedBlocks($this->blocks);
        $assignedBlocks = []; // formatted like: ['POSITION' => [BLOCK MODEL, ...], ...]
        foreach (Yii::$app->big->getFrontendThemePositions() as $name => $title) {
            $ids = $this->_template->getPosition($name);
            $assignedBlocks[$name] = $this->getBlocks($ids);
        }

        $this->registerScripts();

        return $this->render('index', [
            'availableBlocks' => $availableBlocks,
            'assignedBlocks' => $assignedBlocks,
            'columns' => $this->columns,
        ]);
    }

    /**
     * Returns blocks by the provided ids that are assigned to [[blocks]].
     *
     * @param array $ids list of block ids to blocks by.
     * @return array list of blocks found by the provided ids.
     */
    public function getBlocks($ids)
    {
        $blocks = [];
        foreach ($ids as $id) {
            if (isset($this->blocks[$id])) {
                $blocks[] = $this->blocks[$id];
            }
        }
        return $blocks;
    }

    /**
     * Returns a list of blocks that is not used by the current template.
     * 
     * @param array $blocks list of blocks to filter. The keys must be block ids.
     * @return array list of blocks that is not assigned to the current template 
     */
    public function removeAssignedBlocks($blocks)
    {
        $positions = $this->_template->getPositions();
        if (empty($positions)) {
            return $blocks;
        }

        $ids = [];
        foreach ($positions as $position => $blocksIds) {
            $ids = array_merge($ids, $blocksIds);
        }

        $unused = [];
        foreach ($blocks as $block) {
            if (in_array($block['id'], $ids) === false) {
                $unused[] = $block;
            }
        }
        return $unused;
    }

    /**
     * Registers js script that manages the blocks being moved around
     */
    public function registerScripts()
    {
        $view = $this->getView();
        TemplateEditorAsset::register($view);
        $view->registerJs('
            $(".connected").sortable({connectWith: ".connected"}).bind("sortupdate", function(e, ui) {
                var item = $(ui.item);
                var input = item.find("input");
                var parent = item.closest(".block-position");
                if (parent.length) {
                    input.attr("name", "Template[positions]["+parent.data("position")+"][]");
                } else {
                    input.attr("name", "Template[positions][' . Template::UNREGISTERED . '][]");
                }
            }).click(function(e){
                e.preventDefault();
            });
        ', View::POS_END);
    }
}
