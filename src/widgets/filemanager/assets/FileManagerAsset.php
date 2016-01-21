<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\widgets\filemanager\assets;

use yii\web\AssetBundle;

/**
 * FileManagerAsset
 */
class FileManagerAsset extends AssetBundle
{
    public $sourcePath = '@bigbrush/big/widgets/filemanager/elfinder/assets_v2';
    public $css = [
        'css/elfinder.min.css',
    ];
    public $js = [
        'js/elfinder.min.js',
    ];
    public $depends = [
        'yii\jui\JuiAsset',
    ];
}
