<?php

namespace Drupal\commerce_demo_jsonapi;

use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;

class DecoratedResourceTypeRepository extends ResourceTypeRepository {

  /**
   * {@inheritdoc}
   */
  public function all() {
    /** @var \Drupal\jsonapi\ResourceType\ResourceType[] $all */
    $all = parent::all();

    // If bundle collections are removed, things crash.
    foreach ($all as $key => $item) {
      $allowed = [
        'commerce_currency',
        'commerce_store',
        'commerce_product',
        'commerce_product_type',
        'commerce_product_variation',
        'commerce_product_variation_type',
        'commerce_product_attribute_value',
        'commerce_product_attribute',
        'taxonomy_term',
        'taxonomy_vocabulary',
        'node',
        'node_type',
        'user',
        'file',
        'menu_link_content',
      ];
      if (!in_array($item->getEntityTypeId(), $allowed)) {
        unset($all[$key]);
      }
    }

    return $all;
  }

}
