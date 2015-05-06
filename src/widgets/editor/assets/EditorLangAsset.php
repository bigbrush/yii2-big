<?php
/**
 * @copyright Copyright (c) 2013-2015 2amigOS! Consulting Group LLC
 * @link http://2amigos.us
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
namespace bigbrush\big\widgets\editor\assets;

use yii\web\AssetBundle;

class EditorLangAsset extends AssetBundle
{
    public $sourcePath = '@bigbrush/big/widgets/editor';

    public $depends = [
        'bigbrush\big\widgets\editor\assets\EditorAsset',
    ];
}
