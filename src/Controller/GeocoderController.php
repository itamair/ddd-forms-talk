<?php
/**
 * @file
 * Contains \Drupal\ddd_forms_talk\Controller\DefaultController.
 */

namespace Drupal\ddd_forms_talk\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ddd_forms_talk\GeocoderJsonConsumer;

/**
 * Default controller for the geocoder_autocomplete module.
 */
class GeocoderController extends ControllerBase {

  /**
   * Constructs a GeocoderController object.
   *
   * @param \Drupal\ddd_forms_talk\GeocoderJsonConsumer $geocoder
   *   A geocoder service.
   */
  public function __construct(GeocoderJsonConsumer $geocoder) {
    $this->geocoderService = $geocoder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('geocoderautocomplete.consumer')
    );
  }
  /**
   * Geocoder service.
   *
   * @var \Drupal\ddd_forms_talk\GeocoderJsonConsumer
   */
  protected $geocoderService;

  /**
   * Callback Method for Route geocoder_autocomplete.autocomplete.
   *
   * @param Request $request
   *   The Request sent.
   *
   * @return mixed|string
   *   Json output of the found strings.
   */
  public function geocoderAutocomplete(Request $request) {
    $matches = $this->geocoderService->getAddress(
      $request->query->get('q')
    );

    return new JsonResponse($matches);
  }

}
