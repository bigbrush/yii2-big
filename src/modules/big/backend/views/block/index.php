<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->registerJs('
    $("#submitBtn").click(function(){
        if ($("#dropDownList").val() == "") {
            alert("Please select a block");
            return false;
        }
    });
');
?>
<div class="row">
    <div class="col-md-12">
        <h1>Blocks</h1>
        <?= Html::beginForm(['edit'], 'get') ?>
        <div class="row">
            <div class="col-md-2">
                <?= Html::submitButton('Create block', ['class' => 'btn btn-primary', 'id' => 'submitBtn']) ?>
            </div>
            <div class="col-md-10">
                <div class="form-group">
                <?= Html::dropDownList('id', null, $installedBlocks, ['class' => 'form-control', 'id' => 'dropDownList']) ?>
                </div>
            </div>
        </div>
        <?= Html::endForm() ?>
    </div>
</div>

<?php
$chunks = array_chunk($blocks, 4);
foreach ($chunks as $blocks) : ?>
<div class="row">
    <?php foreach ($blocks as $block) : ?>
    <div class="col-md-3" style="margin-bottom:30px;">
        <div class="square">
            <div class="content">
                <?= Html::beginForm(['delete', 'id' => $block['id']]) ?>
                <?= Html::hiddenInput('block_id', $block['id']) ?>
                <?= Html::submitButton('<i class="fa fa-trash"></i>') ?>
                <?= Html::endForm() ?>
                <?= Html::a($block['title'], ['edit', 'id' => $block['id']]) ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>