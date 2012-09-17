<?php
class tmcI18nRouting {
  public static function listenToRoutingLoadConfigurationEvent(sfEvent $event)
  {
    $routing = $event->getSubject();

    $routing->prependRoute('tmc_catalogue', new sfDoctrineRouteCollection(array(
      'name' => 'tmc_catalogue',
      'model' => 'tmcCatalogue',
      'module' => 'catalogue',
      'prefix_path' => '/catalogue',
      'column' => 'cat_id',
      'with_wildcard_routes' => true)
    ));

    $routing->prependRoute('tmc_trans_unit', new sfDoctrineRouteCollection(array(
      'name' => 'tmc_trans_unit',
      'model' => 'tmcTransUnit',
      'module' => 'transunit',
      'prefix_path' => '/translation',
      'column' => 'id',
      'with_wildcard_routes' => true)
    ));
  }
}
