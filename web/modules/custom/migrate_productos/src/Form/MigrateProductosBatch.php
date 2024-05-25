<?php

namespace Drupal\migrate_productos\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Migrate Productos Batch.
 */
class MigrateProductosBatch {

  /**
   * Batch process callback.
   */
  public static function processProduct($product_data, &$context) {

    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['results']['processed'] = 0;
    }

    $context['sandbox']['max'] = count($product_data);

    foreach ($product_data as $product) {
      $node = Node::create([
        'type' => 'producto',
        'title' => $product->title,
        'field_precio' => $product->price,
        'body' => $product->description,
      ]);

      if (!empty($product->images)) {
        $image_references = [];
        foreach ($product->images as $image_url) {
          $parts = explode('/', $image_url);
          $file_name = end($parts);
          $image_data = file_get_contents($image_url);

          $file_repository = \Drupal::service('file.repository');
          $file_system = \Drupal::service('file_system');
          $directory = "public://" . $parts[count($parts) - 2];
          $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

          $image_entity = $file_repository->writeData($image_data, $directory . "/" . $file_name, FileSystemInterface::EXISTS_REPLACE);

          $image_media = Media::create([
            'name' => $file_name,
            'bundle' => 'image',
            'uid' => 1,
            'status' => 1,
            'field_media_image' => [
              'target_id' => $image_entity->id(),
            ],
          ]);

          $image_media->save();

          $image_references[] = [
            'target_id' => $image_media->id(),
          ];
        }

        $node->field_imagenes = $image_references;
      }

      $category = $product->category;
      $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $category]);
      if (!empty($terms)) {
        $term = reset($terms);
      }
      else {
        $term = Term::create([
          'vid' => 'tags',
          'name' => $category,
        ]);
        $term->save();
      }

      $node->field_tags[] = [
        'target_id' => $term->id(),
      ];

      $node->save();

      $context['sandbox']['progress']++;
      $context['results']['processed']++;

      if ($context['sandbox']['progress'] % 10 == 0) {
        $context['message'] = t('Processing @current of @total products.', [
          '@current' => $context['sandbox']['progress'],
          '@total' => $context['sandbox']['max'],
        ]);
        break;
      }
    }

    if ($context['sandbox']['progress'] == $context['sandbox']['max']) {
      $context['finished'] = 1;
    }
    else {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Batch finished callback.
   */
  public static function finishedCallback($success, $results, $operations) {
    if ($success) {
      \Drupal::messenger()->addStatus(t('@count products processed.', ['@count' => $results['processed']]));
    }
    else {
      \Drupal::messenger()->addError(t('Finished with an error.'));
    }
  }

}
