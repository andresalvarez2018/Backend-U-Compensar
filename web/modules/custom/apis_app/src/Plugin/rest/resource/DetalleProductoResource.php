<?php

namespace Drupal\apis_app\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileInterface;
use Drupal\media\Entity\Media;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents Detalle Producto records as resources.
 *
 * @RestResource (
 *   id = "apis_app_detalle_producto",
 *   label = @Translation("Detalle Producto"),
 *   uri_paths = {
 *     "canonical" = "/api/apis-app-detalle-producto/{id}",
 *     "create" = "/api/apis-app-detalle-producto"
 *   }
 * )
 */
class DetalleProductoResource extends ResourceBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DetalleProductoResource object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param int $id
   *   The ID of the record.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the record.
   */
  public function get($id) {
    $node = $this->entityTypeManager->getStorage('node')->load($id);

    if (!$node) {
      return new ResourceResponse([], 404);
    }

    $data = $this->formatNodeData($node);

    return new ResourceResponse($data);
  }

  /**
   * Formats node data.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return array
   *   The formatted node data.
   */
  protected function formatNodeData($node) {
    $description = $node->get('body')->value;
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

    $tags = [];
    foreach ($node->get('field_tags')->referencedEntities() as $term) {
      $tags[] = $term->getName();
    }

    return [
      'id' => $node->id(),
      'title' => $node->getTitle(),
      'description' => $description,
      'price' => $node->get('field_precio')->value,
      'image' => $image_url,
      'tags' => $tags,
    ];
  }

}
