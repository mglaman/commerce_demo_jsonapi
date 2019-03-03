<?php declare(strict_types=1);

namespace Drupal\commerce_demo_jsonapi\EventSubscriber;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Event\FilterVariationsEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class FilterVariationsSubscriber implements EventSubscriberInterface {

  private $requestStack;

  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  public static function getSubscribedEvents(): array {
    return [
      ProductEvents::FILTER_VARIATIONS => 'filterVariations'
    ];
  }

  public function filterVariations(FilterVariationsEvent $event): void {
    if ($this->requestStack->getCurrentRequest()->headers->has('Commerce-Filter-Variations')) {
      $variations = array_filter($event->getVariations(), function (ProductVariationInterface $variation) {
        $filter = $this->requestStack->getCurrentRequest()->headers->get('Commerce-Filter-Variations');
        return strpos($variation->getSku(), $filter) !== FALSE;
      });
      $event->setVariations($variations);
    }
  }
}
