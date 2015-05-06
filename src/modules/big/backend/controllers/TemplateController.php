<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\modules\big\backend\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use bigbrush\big\widgets\templateeditor\TemplateEditor;

/**
 * TemplateController
 */
class TemplateController extends Controller
{
    /**
     * Lists all available templates
     *
     * @return string
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Yii::$app->big->getTemplate()->getModel()->find(),
        ]);
        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates and edits a template
     *
     * @return int $id optional template id to edit. If not provided a new template will be created
     */
    public function actionEdit($id = 0)
    {
        $model = TemplateEditor::getModel($id);
        if (TemplateEditor::save($model)) {
            Yii::$app->getSession()->setFlash('success', 'Template saved');
            $this->redirect(['index']);
        }
        return $this->render('edit', [
            'model' => $model,
        ]);
    }
}