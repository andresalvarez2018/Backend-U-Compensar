<?php

namespace Drupal\migrate_productos\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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
    $response = $client->request('GET', 'https://dummyjson.com/products?limit=100');
    $body = $response->getBody();
    $data = json_decode($body);

    $batch = [
      'title' => $this->t('Migrating products...'),
      'operations' => [],
      'finished' => '\Drupal\migrate_productos\Form\MigrateProductosBatch::finishedCallback',
    ];
    $batch['operations'][] = [
      '\Drupal\migrate_productos\Form\MigrateProductosBatch::processProduct',
        [$data->products],
    ];

    batch_set($batch);
  }

}
