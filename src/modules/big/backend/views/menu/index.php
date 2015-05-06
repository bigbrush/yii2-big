<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\bootstrap\ButtonDropDown;
use yii\bootstrap\Alert;

$this->registerJs('
    function alert(message, type) {
        var button = $("<button>", {
            type: "button",
            class: "close",
            "data-dismiss": "alert",
            "aria-hidden": "true",
            text: "x"
        });
        var alert = $("<div>", {
            class: "alert alert-"+type+" fade in",
        }).css("margin-top", "15px").append(button).append(message);
        $("#alert").empty().html(alert);
    }
    
    $(".changeDirectionBtn").click(function(e){
        var self = $(this),
            direction = self.data("direction"),
            menuId = self.data("pid");

        $.post("'.Url::to(['move']).'", {selected: menuId, direction: direction}, function(data){
            if (data.status === "success") {
                $("#grid").empty().html(data.grid);
            }
            var type = data.status == "error" ? "danger" : data.status;
            alert(data.message, type);
        });

        e.preventDefault();
    });
');
?>
<div class="row">
    <div class="col-md-12">
        <h1>Menu items</h1>
    	<div id="alert">
    	</div>
        <?= Html::a('New menu item', ['edit'], ['class' => 'btn btn-primary']); ?>
        <?= ButtonDropDown::widget([
            'label' => 'Select menu',
            'options' => ['class' => 'btn btn-info'],
            'dropdown' => [
                'items' => $dropdown,
            ],
        ]) ?>
        <?= Html::a('Edit menus', ['menus'], ['class' => 'btn btn-default']); ?>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <?php $form = ActiveForm::begin(['action' => ['move'], 'id' => 'gridForm']); ?>
        <div id="grid">
            <?= $this->render('_grid', ['dataProvider' => $dataProvider]); ?>
        </div>
        <?= Html::hiddenInput('selected', '', ['id' => 'fieldSelected']) ?>
        <?= Html::hiddenInput('direction', '', ['id' => 'fieldDirection']) ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>