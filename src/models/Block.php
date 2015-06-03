<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\models;

use Yii;
use yii\db\ActiveRecord;
use bigbrush\big\models\BlockQuery;
use bigbrush\big\models\Extension;

/**
 * Block
 *
 * @property integer $id
 * @property integer $extension_id
 * @property string $title
 * @property string $content
 * @property string $namespace
 * @property integer $show_title
 * @property integer $state
 * @property integer $scope
 */
class Block extends ActiveRecord
{
    const STATE_INACTIVE = 0;
    const STATE_ACTIVE = 1;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%block}}';
    }

    /**
     * Registers an extension in this block.
     * 
     * @param Extension $extension an extension model to register in this block.
     */
    public function registerExtension($extension)
    {
        $this->namespace = $extension->namespace;
        $this->extension_id = $extension->id;
    }

    /**
     * Returns the extension this block is connected to.
     *
     * @return ActiveQueryInterface the relational query object.
     */
    public function getExtension()
    {
        return $this->hasOne(Extension::className(), ['id' => 'extension_id']);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'extension_id' => Yii::t('big', 'Extension'),
            'title' => Yii::t('big', 'Title'),
            'content' => Yii::t('big', 'Content'),
            'namespace' => Yii::t('big', 'Namespace'),
            'show_title' => Yii::t('big', 'Show title'),
            'state' => Yii::t('big', 'State'),
            'scope' => Yii::t('big', 'Scope'),
        ];
    }

    /**
     * Returns the list of all attribute names of the model.
     * Method overridden to avoid a database call by parent implementation.
     * Block models are populated with data in [[bigbrush\big\core\BlockManager::registerPositions()]] to
     * avoid overhead from a database call. This method needs to be implemented to remove this overhead.
     *
     * @return array list of attribute names used by this AR
     */
    public function attributes()
    {
        return [
            'id',
            'extension_id',
            'title',
            'content',
            'namespace',
            'show_title',
            'state',
            'scope',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'namespace', 'extension_id'], 'required'],
            [['title', 'namespace', 'scope'], 'string', 'max' => 255],
            ['content', 'default', 'value' => ''],
            [['extension_id', 'show_title'], 'integer'],
            ['state', 'default', 'value' => static::STATE_ACTIVE],
            ['state', 'in', 'range' => array_keys($this->getStateOptions())],
        ];
    }

    /**
     * Returns an array of available states.
     * Can be used with form dropdown lists.
     *
     * @return array list of available states
     */
    public static function getStateOptions()
    {
        return [
            static::STATE_ACTIVE => Yii::t('big', 'Active'),
            static::STATE_INACTIVE => Yii::t('big', 'Inactive'),
        ];
    }

    /**
     * Returns the text value of the provided state. If state is not provided the value of [[state]] is returned as text.
     *
     * @param int $state an optional state id to get the value from.
     * @return string the text value.
     */
    public function getStateText($state = null)
    {
        if ($state === null) {
            $state = $this->state;
        }
        $options = $this->getStateOptions();
        return $options[$state];
    }
}
