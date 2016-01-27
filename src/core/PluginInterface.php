<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

/**
 * PluginInterface defines base functionality for all plugins. Specifically it adds the [[register()]] method to plugins. Here
 * plugins can register itself in the manager with appropriate event handlers.
 */
interface PluginInterface
{
    /**
     * Registers appropriate event handlers for this plugin in the manager.
     *
     * @param PluginManager $manager a plugin manager.
     */
    public function register($manager);
}
