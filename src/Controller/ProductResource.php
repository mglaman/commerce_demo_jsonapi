<?php declare(strict_types=1);

namespace Drupal\commerce_demo_jsonapi\Controller;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Event\FilterVariationsEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\jsonapi\Access\EntityAccessChecker;
use Drupal\jsonapi\Context\FieldResolver;
use Drupal\jsonapi\Controller\EntityResource;
use Drupal\jsonapi\IncludeResolver;
use Drupal\jsonapi\JsonApiResource\EntityCollection;
use Drupal\jsonapi\LinkManager\LinkManager;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

final class ProductResource extends EntityResource {

  private $eventDispatcher;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $field_manager,
    LinkManager $link_manager,
    ResourceTypeRepositoryInterface $resource_type_repository,
    RendererInterface $renderer,
    EntityRepositoryInterface $entity_repository,
    IncludeResolver $include_resolver,
    EntityAccessChecker $entity_access_checker,
    FieldResolver $field_resolver,
    $serializer,
    EventDispatcherInterface $event_dispatcher
  ) {
    parent::__construct($entity_type_manager, $field_manager, $link_manager,
      $resource_type_repository, $renderer, $entity_repository,
      $include_resolver, $entity_access_checker, $field_resolver, $serializer);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Gets the related product variations.
   *
   * This mimics the filtering of variations like ::loadEnabled in the
   * ProductVariationStorage implementation.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type for the request to be served.
   * @param \Drupal\commerce_product\Entity\ProductInterface $entity
   *   The requested entity.
   * @param string $related
   *   The related field name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see \Drupal\commerce_product\ProductVariationStorage::loadEnabled
   */
  public function getVariations(ResourceType $resource_type, ProductInterface $entity, $related, Request $request): ResourceResponse {
    // If the user is able to update the product, then they can see all of the
    // variations. This keeps compatibility for administrative consumers that
    // use the `/{entity}/variations` relationship route.
    if ($entity->access('update')) {
      return $this->getRelated($resource_type, $entity, $related, $request);
    }

    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field_list */
    $field_list = $entity->get($resource_type->getInternalName($related));

    // Remove the entities pointing to a resource that may be disabled. Even
    // though the normalizer skips disabled references, we can avoid unnecessary
    // work by checking here too.
    /* @var \Drupal\Core\Entity\EntityInterface[] $referenced_entities */
    $referenced_entities = array_filter($field_list->referencedEntities(),
      function (ProductVariationInterface $entity) {
        return (bool) $this->resourceTypeRepository->get($entity->getEntityTypeId(), $entity->bundle());
      }
    );
    // Remove entities which cannot be viewed.
    $referenced_entities = array_filter($referenced_entities,
      function (ProductVariationInterface $variation) {
      return $variation->access('view');
    });

    // Allow modules to apply own filtering (based on date, stock, etc).
    $event = new FilterVariationsEvent($entity, $referenced_entities);
    $this->eventDispatcher->dispatch(ProductEvents::FILTER_VARIATIONS, $event);
    $referenced_entities = $event->getVariations();

    $collection_data = array_map([$this->entityAccessChecker, 'getAccessCheckedResourceObject'], $referenced_entities);
    $entity_collection = new EntityCollection($collection_data, $field_list->getFieldDefinition()->getFieldStorageDefinition()->getCardinality());
    $response = $this->buildWrappedResponse($entity_collection, $request, $this->getIncludes($request, $entity_collection));

    // $response does not contain the entity list cache tag. We add the
    // cacheable metadata for the finite list of entities in the relationship.
    $response->addCacheableDependency($entity);

    return $response;
  }

}
