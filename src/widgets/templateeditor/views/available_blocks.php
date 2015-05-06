<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

use yii\helpers\Html;
?>
<div class="available-block-list">
    <h3>Available blocks</h3>
    <ul class="nav nav-pills connected" style="min-height:25px;">
        <?php foreach ($blocks as $i => $block) : ?>
        <li>
            <button class="btn btn-default"><?= $block['title'] ?></button>
            <?= Html::hiddenInput('Template[positions][UNREGISTERED][]', $block['id']) ?>
        </li>
        <?php endforeach; ?>
    </ul>
</div>