<?php

namespace Drupal\language_selection_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class LanguageSelectionPageController.
 */
class LanguageSelectionPageController extends ControllerBase {

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
    $manager = \Drupal::getContainer()->get('plugin.manager.language_selection_page_condition');
    foreach ($manager->getDefinitions() as $def) {
      $manager->createInstance($def['id'], $config->get())->alterPageContent($content, $destination);
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

    // Alter the render array.
    $manager = \Drupal::getContainer()->get('plugin.manager.language_selection_page_condition');
    foreach ($manager->getDefinitions() as $def) {
      $manager->createInstance($def['id'], $config->get())->alterPageResponse($response);
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

    $manager = \Drupal::getContainer()->get('plugin.manager.language_selection_page_condition');
    foreach ($manager->getDefinitions() as $def) {
      $destination = $manager->createInstance($def['id'], $config->get())->getDestination($destination);
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
