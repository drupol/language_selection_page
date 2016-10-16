<?php

namespace Drupal\language_selection_page\Plugin\LanguageSelectionPageCondition;

use Drupal\language_selection_page\LanguageSelectionPageConditionBase;
use Drupal\language_selection_page\LanguageSelectionPageConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for the PHP Sapi condition plugin.
 *
 * @LanguageSelectionPageCondition(
 *   id = "php_sapi",
 *   weight = -120,
 *   name = @Translation("PHP SAPI"),
 *   description = @Translation("Bails out when running on command line."),
 *   runInBlock = FALSE,
 * )
 */
class LanguageSelectionPageConditionPHPSapi extends LanguageSelectionPageConditionBase implements LanguageSelectionPageConditionInterface {

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
    if (PHP_SAPI === 'cli') {
      return $this->block();
    }

    return $this->pass();
  }

}
