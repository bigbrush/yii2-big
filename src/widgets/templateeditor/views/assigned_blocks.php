<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

use yii\helpers\Html;
?>
<?php foreach ($blocks as $position => $assignedBlocks) : ?>
<div class="<?= $class ?>">
    <div class="block-position" data-position="<?= $position ?>">
        <h3><small>Position</small> <strong><?= ucfirst($position) ?></strong></h3>
        <ul class="connected" style="min-height:25px;">
            <?php foreach ($assignedBlocks as $block) : ?>
            <li>
                <button class="btn btn-default"><?= $block['title'] ?></button>
                <?= Html::hiddenInput('Template[positions]['.$position.'][]', $block['id']) ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endforeach; ?>