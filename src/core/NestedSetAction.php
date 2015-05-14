<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use \Closure;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Action;
use yii\web\Response;

/**
 * NestedSetAction
 */
class NestedSetAction extends Action
{
    /**
     * The name of the POST parameters indicating id of a database record and a direction to move it.
     */
    const POST_VAR_ID = 'node_id';
    const POST_VAR_DIRECTION = 'direction';
    
    /**
     * @var yii\db\ActiveRecord a model representing a nested set. This property MUST be set.
     */
    public $model;
    /**
     * @var Closure a closure getting called when page content is being updated after an AJAX request.
     */
    public $updateContent;
    /**
     * @var string|array defines a route to redirect to when not responding to an AJAX request.
     * 
     */
    public $redirectTo = ['index'];


    /**
     * Runs the action.
     *
     * @throws InvalidConfigException if model is not an instance of [[yii\db\ActiveRecord]].
     * @throws BadRequestHttpException if posted form data is not valid.
     */
    public function run()
    {
        if (!$this->model instanceof yii\db\ActiveRecord) {
            throw new InvalidConfigException("Model attribute in NestedSetAction must be populated with a yii\db\ActiveRecord.");   
        }

        $request = Yii::$app->getRequest();
        $id = (int)$request->post(self::POST_VAR_ID);
        $direction = $request->post(self::POST_VAR_DIRECTION);

        if (empty($id) || empty($direction)) {
            throw new BadRequestHttpException("Form not valid - 'node_id' and 'direction' must be set in POST.");
        }

        $model = $this->model->findOne($id);
        if ($model) {
            $result = $this->moveNode($model, $direction);
        } else {
            $result = [
                'status' => 'error',
                'message' => 'Model with id: "'.$id.'" not found',
            ];
        }

        if ($request->getIsAjax()) {
            Yii::$app->getResponse()->format = Response::FORMAT_JSON;
            if ($result['status'] === 'success') {
                if ($this->updateContent instanceof Closure) {
                	$closure = $this->updateContent;
                    $result['grid'] = $closure();
                } else {
                    $result['grid'] = '';
                }
            }
            return $result;
        } else {
            Yii::$app->getSession()->setFlash($result['status'], $result['message']);
            return $this->controller->redirect($this->redirectTo);
        }
    }

    /**
     * Moves a nested set node up or down.
     *
     * @param yii\db\ActiveRecord $model a model to move in the tree.
     * @param string $direction the direction to move the model. Can be "up" or "down".
     * @return array status array with the keys "status" and "message".
     */
    public function moveNode($model, $direction)
    {
        $message = '';
        $status = 'success';

        if ($direction === 'up') {
            if (($prev = $model->prev()->one()) !== null) {
                if ($model->insertBefore($prev)) {
                    $message = 'Menu item moved successfully';
                } else {
                    $status = 'error';
                    $message = 'An error occured. Please try again';
                }
            } else {
                $status = 'info';
                $message = 'Menu item not moved. It is the first item';
            }
        } elseif ($direction === 'down') {
            if (($next = $model->next()->one()) !== null) {
                if ($model->insertAfter($next)) {
                    $message = 'Menu item moved successfully';
                } else {
                    $status = 'error';
                    $message = 'An error occured. Please try again';
                }
            } else {
                $status = 'info';
                $message = 'Menu item not moved. It is the last item';
            }
        } else {
            $message = 'Direction can only be "up" or "down"';
            $status = 'error';
        }

        return [
            'status' => $status,
            'message' => $message,
        ];
    }
}