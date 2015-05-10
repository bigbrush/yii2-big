<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\modules\big\backend\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\web\Controller;
use yii\web\MethodNotAllowedHttpException;

/**
 * BlockController
 */
class BlockController extends Controller
{
    /**
     * Show a page with all created blocks
     *
     * @return string
     */
    public function actionIndex()
    {
        $manager = Yii::$app->big->blockManager;
        $blocks = $manager->find()->all();
        $installedBlocks = ['' => 'Select block'] + $manager->getInstalledBlocks();
        return $this->render('index', [
            'blocks' => $blocks,
            'installedBlocks' => $installedBlocks,
        ]);
    }

    /**
     * Edit and create a block
     *
     * @param string|int $id if an integer is provided it is regarded as database record. If it is a
     * string it is regarded as a new block.
     * @return string
     * @throws MethodNotAllowedHttpException if form is posted and 'Block' is not a key in $_POST
     */
    public function actionEdit($id)
    {
        $block = Yii::$app->big->blockManager->createBlock($id);
        $model = $block->model;
        if ($model->getIsNewRecord()) {
            $model->name = $id;
            $model->show_title = true;
        }
        $post = Yii::$app->getRequest()->post();
        if ($model->load($post)) {
            if ($model->save()) {
                Yii::$app->getSession()->setFlash('success', 'Block saved');
                return $this->redirect(['index']);
            }
        } elseif (!empty($post)) {
            throw new MethodNotAllowedHttpException("Form not saved because 'Block' was not set in $_POST");
        }
        return $this->render('edit', [
            'block' => $block,
        ]);
    }
}