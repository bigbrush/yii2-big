<?php
/**
 * @copyright Copyright (c) 2013-2015 2amigOS! Consulting Group LLC
 * @link http://2amigos.us
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
namespace bigbrush\big\widgets\filemanager\assets;

use Yii;
use yii\base\InvalidParamException;
use yii\web\AssetBundle;

class FileManagerLangAsset extends AssetBundle
{
    public $sourcePath = '@bigbrush/big/widgets/filemanager/elfinder/js/i18n';

    public $depends = [
        'bigbrush\big\widgets\filemanager\assets\FileManagerAsset',
    ];
}
