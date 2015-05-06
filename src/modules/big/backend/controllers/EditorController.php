<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\modules\big\backend\controllers;

use Yii;
use yii\web\Controller;
use bigbrush\big\widgets\filemanager\FileManager;

/**
 * EditorController
 */
class EditorController extends Controller
{
    /**
     * Renders a list with all application links
     *
     * @return string
     */
    public function actionGetLinks()
    {
        return $this->renderAjax('links');
    }

    /**
     * Renders the UI for the media folder
     *
     * @return string
     */
    public function actionGetMedia()
    {
        return $this->renderAjax('media');
    }
}