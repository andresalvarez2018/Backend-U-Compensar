<?php

namespace Drupal\apis_app\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\media\Entity\Media;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents List Productos records as resources.
 *
 * @RestResource (
 *   id = "apis_app_list_productos",
 *   label = @Translation("List Productos"),
 *   uri_paths = {
 *     "canonical" = "/api/apis-app-list-productos"
 *   }
 * )
 */
class ListProductosResource extends ResourceBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->storage = $keyValueFactory->get('apis_app_list_productos');
    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('keyvalue'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the records.
   */
  public function get() {
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'producto')
      ->execute();

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    $data = [];
    foreach ($nodes as $node) {
      $body = $node->get('body')->value;
      $description = substr($body, 0, 100);

      $image_url = '';
      $image_field = $node->get('field_imagenes')->getString();

      if ($image_field) {
        $media_entity = Media::load($image_field);
        if ($media_entity) {
          $file_id = $media_entity->getSource()->getSourceFieldValue($media_entity);
          $bg_file = \Drupal::entityTypeManager()->getStorage('file')->load($file_id);

          if ($bg_file) {
            $path = $bg_file->createFileUrl();
            $image_url = \Drupal::request()->getSchemeAndHttpHost() . $path;
          }
        }
      }

      $taxonomy_terms = $node->get('field_tags')->referencedEntities();
      $tags = [];
      foreach ($taxonomy_terms as $term) {
        $tags[] = $term->getName();
      }

      if ($image_url) {
        $data[] = [
          'id' => $node->id(),
          'title' => $node->getTitle(),
          'description' => $description,
          'price' => $node->get('field_precio')->value,
          'image' => $image_url,
          'tags' => $tags,
        ];
      }

    }

    return new ResourceResponse($data);
  }

}
