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
 * MediaController
 */
class MediaController extends Controller
{
    /**
     * Renders the file manager.
     */
    public function actionShow()
    {
        return $this->render('media');
    }
    
    /**
     * Updates the file manager UI.
     *
     * @return string
     */
    public function actionUpdate()
    {
        FileManager::widget([
            'state'=> FileManager::STATE_UPDATE,
        ]);
    }
}