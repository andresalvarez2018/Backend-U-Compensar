<?php

namespace Drupal\apis_app\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Represents Login records as resources.
 *
 * @RestResource (
 *   id = "apis_app_login",
 *   label = @Translation("Login"),
 *   uri_paths = {
 *     "create" = "/api/apis-app-login"
 *   }
 * )
 */
class LoginResource extends ResourceBase {

  /**
   * The key-value storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $storage;

  /**
   * The password hashing service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $passwordChecker;

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
    PasswordInterface $passwordChecker,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->storage = $keyValueFactory->get('apis_app_login');
    $this->passwordChecker = $passwordChecker;
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
      $container->get('password')
    );
  }

  /**
   * Responds to POST requests and saves the new record.
   *
   * @param array $data
   *   Data to write into the database.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The HTTP response object.
   */
  public function post(array $data) {
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (!empty($username) && !empty($password)) {
      $account = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $username]);

      if (!empty($account)) {
        $account = reset($account);

        if ($this->passwordChecker->check($password, $account->getPassword())) {
          return new JsonResponse(
            [
              'message' => 'Inicio de sesión correcto',
            ],
            200);
        }
      }
    }

    return new JsonResponse(
      [
        'message' => 'Usuario o Contraseña Incorrectos',
      ],
      401);
  }

}
