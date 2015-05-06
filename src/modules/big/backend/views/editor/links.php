<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\bootstrap\ButtonDropDown;
use bigbrush\big\widgets\bigsearch\BigSearch;
use bigbrush\big\widgets\filemanager\FileManager;

$this->registerJs('
    $(".section-wrapper").first().addClass("selected-section").show();
    $(".insert-on-click").click(function(e){
        e.preventDefault();
        var route = $(this).data("route");
        top.tinymce.activeEditor.windowManager.getParams().setUrl(route);
    });
    $("#sections-dropdown .section-selector").click(function(e){
        e.preventDefault();
        $(".selected-section").removeClass("selected-section").hide();
        var section = $(this).data("section");
        $("#"+section).addClass("selected-section").show();
    });
');

$widget = Yii::createObject([
    'class' => BigSearch::className(),
    'dynamicUrls' => true,
]);
$sections = $widget->triggerSearch();
$buttons = $widget->createDropDownButtons(array_keys($sections));
$buttons[] = [
    'label' => 'Media',
    'url' => '#',
    'linkOptions' => [
        'class' => 'section-selector',
        'data-section' => 'section-' . count($buttons),
    ]
];

/**
 * The file manager must be created first so jquery-ui is loaded first and
 * bootstrap afterwards. Otherwise jquery-ui will break the [[ButtonDropDown]]
 * widget UI.
 */
$FileManager = FileManager::widget([
    'getFileCallback' => 'function(file){
        top.tinymce.activeEditor.windowManager.getParams().setMedia(file.url);
    }'
]);
$ButtonDropDown = ButtonDropDown::widget([
    'label' => 'Select section',
    'options' => ['class' => 'btn btn-info'],
    'dropdown' => [
        'options' => ['id' => 'sections-dropdown'],
        'items' => $buttons
    ]
]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <title>Insert link</title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="container-fluid">
    
    <div class="row" style="margin-top:5px; margin-bottom:15px;">
        <div class="col-md-12">
            <?= $ButtonDropDown ?>
        </div>
    </div>

    <?php
    $counter = 0;
    foreach ($sections as $section => $items) : ?>
    <div id="section-<?= $counter++ ?>" class="section-wrapper" style="display:none;">
        <div class="row">
            <div class="col-md-12">
                <?= GridView::widget([
                    'dataProvider' => new ArrayDataProvider(['allModels' => $items]),
                    'columns' => [
                        [
                            'header' => 'Title',
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

    <div id="section-<?= $counter++ ?>" class="section-wrapper" style="display:none;">
        <div class="row">
            <div class="col-md-12">
                <?= $FileManager ?>
            </div>
        </div>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>