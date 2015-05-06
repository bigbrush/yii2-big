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

<div class="row">
    <div class="col-md-12">
	<?php foreach ($blocks as $block) : ?>
        <div class="square">
	        <div class="content green">
	        	<p><?= Html::a($block['title'], ['edit', 'id' => $block['id']]) ?></p>
	        </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>