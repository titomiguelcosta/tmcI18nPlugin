<?php

/**
 * PlugintmcTransUnit form.
 *
 * @package    ##PROJECT_NAME##
 * @subpackage form
 * @author     ##AUTHOR_NAME##
 * @version    SVN: $Id: sfDoctrineFormPluginTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PlugintmcTransUnitForm extends BasetmcTransUnitForm
{
  public function  setup()
  {
    parent::setup();
    unset($this['date_created'], $this['date_modified']);
  }
}
