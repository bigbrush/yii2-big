<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
?>
<div class="row">
    <div class="col-md-12">
        <p>
            <?= Html::a('New template', ['edit'], ['class' => 'btn btn-primary']); ?>
        </p>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <h1>Templates</h1>
        <?php echo GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                'id',
                [
                    'header' => 'Title',
                    'format' => 'raw',
                    'value' => function($data) {
                        return Html::a($data->title, ['edit', 'id' => $data->id]);
                    },
                ],
            ],
        ]); ?>
    </div>
</div>