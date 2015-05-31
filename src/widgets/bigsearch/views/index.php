<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;
use yii\bootstrap\ButtonDropDown;
?>
<div class="row">
    <div class="col-md-12">
        <?= ButtonDropDown::widget([
            'label' => Yii::t('big', 'Select section'),
            'options' => ['class' => 'btn btn-info'],
            'dropdown' => [
                'options' => ['id' => 'sections-dropdown'],
                'items' => $buttons
            ]
        ]); ?>
    </div>
</div>

<div id="all-sections-wrap">
    <?php
    /**
     * counter used with BigSearch::createDropDownButtons()
     */
    $counter = 0;
    foreach ($sections as $section => $items) : ?>
    <div id="section-<?= $counter++ ?>" class="section-wrapper" style="display:none;">
        <div class="row">
            <div class="col-md-12">
                <?= GridView::widget([
                    'dataProvider' => new ArrayDataProvider(['allModels' => $items]),
                    'columns' => [
                        [
                            'header' => Yii::t('big', 'Title'),
                            'format' => 'raw',
                            'options' => ['width' => '75%'],
                            'value' => function($data){
                                return Html::a($data['title'], '#', ['data-route' => $data['route'], 'class' => 'insert-on-click']);
                            },
                        ],
                        'section',
                    ],
            ]); ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>