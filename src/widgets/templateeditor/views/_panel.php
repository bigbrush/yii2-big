<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

use yii\helpers\Html;
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <strong><?= $heading ?></strong>
    </div>
    <div class="panel-body">
        <ul class="connected">
            <?php
            foreach ($blocks as $block) {
                $content = Html::tag('span', $block['title']) . "\n";
                $content .= Html::hiddenInput('Template[positions][' . $position . '][]', $block['id']);
                echo Html::tag('li', $content);
            }
            ?>
        </ul>
    </div>
</div>
