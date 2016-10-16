<?php

namespace Drupal\language_selection_page\Plugin\LanguageSelectionPageCondition;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\language_selection_page\LanguageSelectionPageConditionBase;
use Drupal\language_selection_page\LanguageSelectionPageConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for the Language Prefixes plugin.
 *
 * @LanguageSelectionPageCondition(
 *   id = "language_prefixes",
 *   weight = -110,
 *   name = @Translation("Language prefixes"),
 *   description = @Translation("Bails out when enabled languages doesn't have prefixes."),
 *   runInBlock = TRUE,
 * )
 */
class LanguageSelectionPageConditionLanguagePrefixes extends LanguageSelectionPageConditionBase implements LanguageSelectionPageConditionInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a LanguageSelectionPageConditionLanguagePrefixes plugin.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('language_manager'),
      $container->get('config.factory'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $languages = $this->languageManager->getNativeLanguages();
    $language_negotiation_config = $this->configFactory->get('language.negotiation')->get('url');
    $prefixes = array_filter($language_negotiation_config['prefixes']);

    if (count($languages) != count($prefixes)) {
      return $this->block();
    }

    return $this->pass();
  }

}
