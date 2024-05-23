<?php

namespace Drupal\migrate_productos\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use GuzzleHttp\Client;

/**
 * Provides a Migrate Productos form.
 */
class MigrateProductosForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_productos_migrate_productos';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Migrate Products'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $client = new Client();
    $response = $client->request('GET', 'https://dummyjson.com/products');
    $body = $response->getBody();
    $data = json_decode($body);

    foreach ($data->products as $product_data) {
      $node = Node::create([
        'type' => 'producto',
        'title' => $product_data->title,
        'field_precio' => $product_data->price,
        'body' => $product_data->description,
      ]);

      if (!empty($product_data->images)) {
        $image_references = [];
        foreach ($product_data->images as $image_url) {
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

      $category = $product_data->category;

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
    }

    $this->messenger()->addStatus($this->t('Carga Exitosa'));
    $form_state->setRedirect('<front>');
  }

}
