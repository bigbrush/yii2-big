<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
?>
<div class="row">
    <div class="col-md-12">
        <?php $form = ActiveForm::begin(); ?>
            <p>
                <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
                <?= Html::a('Back', ['menus'], ['class' => 'btn btn-danger']); ?>
            </p>
            <h1>Edit menu</h1>
            <?= $form->field($model, 'title') ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>