<?php

namespace Drupal\apis_app\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents Guardar ubicacion geolocalizacion records as resources.
 *
 * @RestResource (
 *   id = "apis_app_guardar_ubicacion_geolocalizacion",
 *   label = @Translation("Guardar ubicacion geolocalizacion"),
 *   uri_paths = {
 *     "canonical" = "/api/apis-app-guardar-ubicacion-geolocalizacion/{id}",
 *     "create" = "/api/apis-app-guardar-ubicacion-geolocalizacion"
 *   }
 * )
 *
 * @DCG
 * The plugin exposes key-value records as REST resources. In order to enable it
 * import the resource configuration into active configuration storage. An
 * example of such configuration can be located in the following file:
 * core/modules/rest/config/optional/rest.resource.entity.node.yml.
 * Alternatively you can enable it through admin interface provider by REST UI
 * module.
 * @see https://www.drupal.org/project/restui
 *
 * @DCG
 * Notice that this plugin does not provide any validation for the data.
 * Consider creating custom normalizer to validate and normalize the incoming
 * data. It can be enabled in the plugin definition as follows.
 * @code
 *   serialization_class = "Drupal\foo\MyDataStructure",
 * @endcode
 *
 * @DCG
 * For entities, it is recommended to use REST resource plugin provided by
 * Drupal core.
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class GuardarUbicacionGeolocalizacionResource extends ResourceBase {

  /**
   * The key-value storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    KeyValueFactoryInterface $keyValueFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $keyValueFactory);
    $this->storage = $keyValueFactory->get('apis_app_guardar_ubicacion_geolocalizacion');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('keyvalue')
    );
  }

  /**
   * Responds to POST requests and saves the new record.
   *
   * @param array $data
   *   Data to write into the database.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function post(array $data) {
    if (!isset($data['latitude']) || !isset($data['longitude']) || !isset($data['username'])) {
      return new ModifiedResourceResponse(
        [
          'error' => 'Los campos latitude, longitude y username son obligatorios.',
        ],
        400);
    }

    $username = $data['username'];
    $latitude = $data['latitude'];
    $longitude = $data['longitude'];

    $account = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(
      [
        'name' => $username,
      ]
    );

    if (!$account) {
      return new ModifiedResourceResponse(
        [
          'error' => 'El usuario proporcionado no existe.',
        ],
        404);
    }

    $user = reset($account);

    $user->set('field_latitude', $latitude);
    $user->set('field_longitude', $longitude);
    $user->save();

    return new ModifiedResourceResponse(
      [
        'message' => 'Ubicación actualizada correctamente para el usuario ' . $username,
      ],
      200);
  }

}
