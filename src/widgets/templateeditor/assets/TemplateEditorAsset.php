<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\widgets\templateeditor\assets;

use yii\web\AssetBundle;

/**
 * TemplateEditorAsset
 */
class TemplateEditorAsset extends AssetBundle
{
    public $sourcePath = '@bigbrush/big/widgets/templateeditor';
    public $js = [
        'js/html.sortable.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}