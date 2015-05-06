<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\widgets\templateeditor;

use Yii;
use yii\base\Widget;
use yii\web\View;
use bigbrush\big\widgets\templateeditor\assets\TemplateEditorAsset;

/**
 * TemplateEditor
 * 
 * Use this widget to include the block UI in your module
 * This widget must be used within a form.
 */
class TemplateEditor extends Widget
{
    /**
     * @var int|bigbrush\big\core\Template|bigbrush\big\models\Template the active template, a template model or a template ID.
     * The default template will be used if this property is not set.
     * See [[init()]] for initialization of this property.
     */
    public $template;
    /**
     * @var string will be used as class for a wrapper <div> when calling
     * the end method of the widget. Note that this property needs to be
     * setup in the begin method.
     */
    public $wrapperClass = 'col-md-3';
    /**
     * @var array list of all blocks available to assign to positions
     */
    public $blocks;


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
    public static function getModel($id = null)
    {
        return Yii::$app->big->getTemplate()->getModel($id);
    }

    /**
     * Renders all available blocks
     * This is triggered by the begin() method of the widget
     */
    public function init()
    {
        if ($this->template === null || is_numeric($this->template)) {
            $this->template = Yii::$app->big->getTemplate()->load($this->template);
        } elseif ($this->template instanceof yii\db\ActiveRecord) {
            Yii::$app->big->getTemplate()->configure($this->template->getAttributes(['id', 'title', 'positions', 'is_default']));
        }
        if ($this->blocks === null) {
            $this->blocks = Yii::$app->big->blockManager->find()->indexBy('id')->all();
        }
        $blocks = $this->removeAssignedBlocks($this->blocks);
        echo $this->render('available_blocks', [
            'blocks' => $blocks,
        ]);
    }

    /**
     * Renders all positions and assigned blocks to each position.
     * This is triggered by the end() method of the widget
     *
     * @return string the result of widget execution to be outputted.
     */
    public function run()
    {
        $positions = Yii::$app->big->getFrontendLayoutFilePositions();
        $blocks = []; // formatted like: ['POSITION' => [BLOCK MODEL, ...], ...]
        $template = $this->template;
        foreach ($positions as $id => $name) {
            $blocks[$id] = [];
            if (isset($template->positions[$id])) {
                foreach ($template->positions[$id] as $blockId) {
                    if (isset($this->blocks[$blockId])) {
                        $blocks[$id][] = $this->blocks[$blockId];
                    }
                }
            }
        }
        $this->registerScripts();

        return $this->render('assigned_blocks', [
            'blocks' => $blocks,
            'class' => $this->wrapperClass,
        ]);
    }

    /**
     * Returns a list of blocks that is not used by the current template
     * 
     * @param array $blocks list of blocks to filter
     * @return array list of blocks that is not assigned to the current template 
     */
    public function removeAssignedBlocks($blocks)
    {
        if (empty($this->template->positions)) {
            return $blocks;
        }
        $ids = [];
        foreach ($this->template->positions as $position => $blocksIds) {
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
            $(function() {
            $(".connected").sortable({connectWith: ".connected"}).bind("sortupdate", function(e, ui) {
                var item = $(ui.item);
                var input = item.find("input");
                var parent = item.closest(".block-position");
                if (parent.length) {
                    input.attr("name", "Template[positions]["+parent.data("position")+"][]");
                } else {
                    input.attr("name", "Template[positions][UNREGISTERED][]");
                }
            });
            });
        ', View::POS_END);
    }
}