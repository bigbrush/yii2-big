<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use bigbrush\big\widgets\templateeditor\TemplateEditor;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model bigbrush\big\models\PageTemplate */
?>
<?= Yii::$app->session->getFlash('error'); ?>

<?php $form = ActiveForm::begin(); ?>
<div class="row">
    <div class="col-md-12">
        <h1>Edit template</h1>
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Back', ['index'], ['class' => 'btn btn-danger']) ?>
        <?= $form->field($model, 'title') ?>
        <?= $form->field($model, 'is_default')->dropDownList(['No', 'Yes']) ?>
    </div>
<div class="row">
    <div class="col-md-12">
        <?php TemplateEditor::begin(['template' => $model]); ?>
    </div>
</div>
<div class="row">
    <?php TemplateEditor::end(); ?>
</div>
<?php ActiveForm::end(); ?>