<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\Object;
use yii\db\Query;
use yii\helpers\Json;

/**
 * TemplateManagerObject
 */
class TemplateManagerObject extends ManagerObject
{
    /**
     * Returns all positions and assigned blocks used in this template.
     * This method returns an array where the keys are position names and
     * the values are arrays of blocks ids assigned to the position.
     *
     * If an array of positions is provided only mathcing positions from this
     * template is returned. If none of the provided positions is assigned an
     * empty array is returned.
     *
     * @param array $names optional list of position names.
     * If not provided all positions assigned in this template is returned.
     * @return array list of positions assigned to this template.
     * @see [[getPosition()]]
     */
    public function getPositions(array $names = [])
    {
        if (empty($names)) {
            return $this->positions;
        }

        $positions = [];
        foreach ($names as $name) {
            $ids = $this->getPosition($name);
            if (!empty($ids)) {
                $positions[$name] = $ids;
            }
        }
        return $positions;
    }

    /**
     * Returns blocks ids assigned to the provided position. If the provided position
     * is not registered in this template an empty array is returned.
     *
     * @param string $name a position name
     * @return array an array of block ids if the position exists. An empty array if
     * the provided position is not registered.
     */
    public function getPosition($name)
    {
        if (isset($this->positions[$name])) {
            return $this->positions[$name];
        } else {
            return [];
        }
    }

    /**
     * Returns true if this template is the default template.
     *
     * @return boolean true if this is the default template, otherwise false.
     */
    public function getIsDefault()
    {
        return $this->is_default === 1;
    }
}
