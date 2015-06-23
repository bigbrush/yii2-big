<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

use yii\helpers\Html;
use bigbrush\big\models\Template;

$this->registerCss('
    li.sortable-placeholder {
        border: 1px dashed #CCC;
        background: none;
        margin: 0 auto;
        min-height: 25px;
    }
');

$chunks = array_chunk($assignedBlocks, $columns, $preserveKeys = true);
$rowClass = 'col-md-' . 12 / $columns;
?>
<div id="<?= $id ?>" class="template-editor">
    <div class="row">
        <div class="available-blocks">
            <div class="col-md-3">
                <?= $this->render('_panel', [
                    'heading' => Yii::t('big', 'Available blocks'),
                    'blocks' => $availableBlocks,
                    'position' => Template::UNREGISTERED,
                ]) ?>
            </div>
        </div>
        <div class="assigned-blocks">
            <div class="col-md-9">
                <?php foreach ($chunks as $assignedBlocks) : ?>
                <div class="row">
                    <?php foreach ($assignedBlocks as $position => $blocks) : ?>
                    <div class="<?= $rowClass ?> block-position" data-position="<?= $position ?>">
                        <?= $this->render('_panel', [
                            'heading' => ucfirst($position),
                            'blocks' => $blocks,
                            'position' => $position,
                        ]) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
