<?php

/**
 * tmcI18nPlugin configuration.
 *
 * @package    tmcI18nPlugin
 * @subpackage config
 * @author     Tito Miguel Costa <symfony@titomiguelcosta.com>
 * @version    SVN: $Id: tmcI18nPluginConfiguration.class.php
 */
class tmcI18nPluginConfiguration extends sfPluginConfiguration
{
  /**
   * @see sfPluginConfiguration
   */
  public function initialize()
  {
    $this->dispatcher->connect('routing.load_configuration', array('tmcI18nRouting', 'listenToRoutingLoadConfigurationEvent'));
  }
}