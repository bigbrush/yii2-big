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
    
    var wrapper = $("#grid");
    $("#grid").on("click", ".changeDirectionBtn", function(e){
        var self = $(this),
            direction = self.data("direction"),
            menuId = self.data("pid");

        $.post("'.Url::to(['move']).'", {node_id: menuId, direction: direction}, function(data){
            if (data.status === "success") {
                wrapper.empty().html(data.grid);
            }
            var type = data.status == "error" ? "danger" : data.status;
            alert(data.message, type);
        }, "json");

        e.preventDefault();
    });
');
?>
<div class="row">
    <div class="col-md-12">
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
        <h1>Menu items</h1>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div id="grid">
            <?= $this->render('_grid', ['dataProvider' => $dataProvider]); ?>
        </div>
    </div>
</div>