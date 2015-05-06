<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\widgets\recorder;

use Yii;
use yii\base\Widget;

/**
 * Recorder
 */
class Recorder extends Widget
{
    /**
     * @var string position of this block
     */
    public $position;


    /**
     * Starts recording a block.
     * @throws InvalidConfigException if [[position]] is not set.
     */
    public function init()
    {
    	if(!$this->position) {
    	    throw new InvalidConfigException('The "position" is required when recording a Block');
    	}
        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * Ends recording a block.
     * This method stops output buffering and saves the rendering result as a named block in Big
     */
    public function run()
    {
        Yii::$app->big->blockManager->addBlock($this->position, ob_get_clean());
    }
}
