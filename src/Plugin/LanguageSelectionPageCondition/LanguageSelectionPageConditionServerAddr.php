<?php

namespace Drupal\language_selection_page\Plugin\LanguageSelectionPageCondition;

use Drupal\language_selection_page\LanguageSelectionPageConditionBase;
use Drupal\language_selection_page\LanguageSelectionPageConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for the Server Addr condition plugin.
 *
 * @LanguageSelectionPageCondition(
 *   id = "server_addr",
 *   weight = -70,
 *   name = @Translation("Server Address condition check"),
 *   description = @Translation("Bails out if the server address is not set."),
 *   runInBlock = FALSE,
 * )
 */
class LanguageSelectionPageConditionServerAddr extends LanguageSelectionPageConditionBase implements LanguageSelectionPageConditionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (isset($_SERVER['SERVER_ADDR'])) {
      return $this->pass();
    }

    return $this->block();
  }

}
