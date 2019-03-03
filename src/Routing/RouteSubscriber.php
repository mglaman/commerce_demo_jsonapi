<?php declare(strict_types=1);

namespace Drupal\commerce_demo_jsonapi\Routing;

use Drupal\commerce_product\Entity\ProductType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\RouteCollection;

final class RouteSubscriber extends RouteSubscriberBase {

  private $productTypeStorage;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->productTypeStorage = $entity_type_manager->getStorage('commerce_product_type');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    /** @var \Drupal\commerce_product\Entity\ProductType[] $product_types */
    $product_types = $this->productTypeStorage->loadMultiple();
    foreach ($product_types as $product_type) {
      $variations_related_route = $collection->get("jsonapi.commerce_product--{$product_type->id()}.variations.related");
      if ($variations_related_route !== NULL) {
        $variations_related_route->setDefault(RouteObjectInterface::CONTROLLER_NAME, 'commerce_demo_jsonapi.entity_resource:getVariations');
      }
    }
  }

}
