<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\widgets\editor\assets;

use yii\web\AssetBundle;

/**
 * EditorAsset
 */
class EditorAsset extends AssetBundle
{
    public $sourcePath = '@vendor/tinymce/tinymce';

    /**
     * Intializes javascript the tinymce javascript file depending on the environment
     */
    public function init()
    {
        parent::init();
        $this->js[] = YII_DEBUG ? 'tinymce.js' : 'tinymce.min.js';
    }
}