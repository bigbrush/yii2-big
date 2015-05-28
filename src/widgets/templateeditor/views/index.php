<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

use yii\helpers\Html;
use bigbrush\big\models\Template;

?>
<div id="template-editor" class="row">

    <div class="col-md-3">
        <div id="available-blocks">
            <h3 class="section-title">Available blocks</h3>
            <ul class="connected">
                <?php
                foreach ($availableBlocks as $block) {
                    $content = Html::tag('span', $block['title']);
                    $content .= Html::hiddenInput('Template[positions][' . Template::UNREGISTERED . '][]', $block['id']);
                    echo Html::tag('li', $content);
                }
                ?>
            </ul>
        </div>
    </div>

    <?php
    $chunks = array_chunk($assignedBlocks, $columns, true);
    $class = 'col-md-' . 12 / $columns;
    ?>
    
    <div class="col-md-9">
        <div id="assigned-blocks">
            <?php foreach ($chunks as $assignedBlocks) : ?>
            <div class="row">
        
                <?php foreach ($assignedBlocks as $position => $blocks) : ?>
                <div class="<?= $class ?>">
                    <div class="block-position" data-position="<?= $position ?>">
                        <h3><strong><?= ucfirst($position) ?></strong></h3>
                        <ul class="connected">
                            <?php
                            foreach ($blocks as $block) {
                                $content = Html::tag('span', $block['title']);
                                $content .= Html::hiddenInput('Template[positions]['.$position.'][]', $block['id']);
                                echo Html::tag('li', $content);
                            }
                            ?>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
        
            </div>
        <?php endforeach; ?>
        </div>
    </div>

</div>
