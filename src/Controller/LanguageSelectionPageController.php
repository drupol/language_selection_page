<?php

namespace Drupal\language_selection_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class LanguageSelectionPageController.
 */
class LanguageSelectionPageController extends ControllerBase {

  /**
   * The plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $pluginManager;

  /**
   * PageController constructor.
   *
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $plugin_manager
   *   The plugin manager service.
   */
  public function __construct(ExecutableManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.language_selection_page_condition')
    );
  }

  /**
   * Get the content of the Language Selection Page.
   *
   * Method used in LanguageSelectionPageController::main().
   *
   * @param string $destination
   *   The destination.
   *
   * @return array
   *   A render array.
   */
  public function getPageContent($destination = '<front>') {
    $config = $this->config('language_selection_page.negotiation');
    $content = [];

    // Alter the render array.
    foreach ($this->pluginManager->getDefinitions() as $def) {
      $this->pluginManager->createInstance($def['id'], $config->get())->alterPageContent($content, $destination);
    }

    return $content;
  }

  /**
   * Get the response.
   *
   * @param array $response
   *   The content array.
   *
   * @return \Symfony\Component\HttpFoundation\Response|array
   *   A response or a render array.
   */
  public function getPageResponse($response) {
    $config = $this->config('language_selection_page.negotiation');

    foreach ($this->pluginManager->getDefinitions() as $def) {
      $this->pluginManager->createInstance($def['id'], $config->get())->alterPageResponse($response);
    }

    return $response;
  }

  /**
   * Get the destination.
   *
   * Loop through each plugins to find it.
   *
   * @param string $destination
   *   The destination.
   *
   * @return string
   *   The destination.
   */
  public function getDestination($destination = NULL) {
    $config = $this->config('language_selection_page.negotiation');

    foreach ($this->pluginManager->getDefinitions() as $def) {
      $destination = $this->pluginManager->createInstance($def['id'], $config->get())->getDestination($destination);
    }

    return $destination;
  }

  /**
   * Page callback.
   */
  public function main() {
    $config = $this->config('language_selection_page.negotiation');
    $destination = $this->getDestination();

    // Check $destination is valid.
    // If the path is set to $destination, redirect the user to the
    // front page to avoid infinite loops.
    if (empty($destination) || (trim($destination, '/') == trim($config->get('path'), '/'))) {
      return new RedirectResponse(Url::fromRoute('<front>')->setAbsolute()->toString());
    }

    return $this->getPageResponse($this->getPageContent($destination));
  }

}
