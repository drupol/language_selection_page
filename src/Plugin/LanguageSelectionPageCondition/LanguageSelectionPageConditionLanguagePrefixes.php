<?php

namespace Drupal\language_selection_page\Plugin\LanguageSelectionPageCondition;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
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

  /**
   * {@inheritdoc}
   */
  public function alterPageContent(array &$content = array(), $destination = '<front>') {
    $links = [];

    // As we are generating a URL from user input, we need to catch any
    // exceptions thrown by invalid paths.
    try {
      // TODO: This variable will be used in the template.
      // TODO: We still have to decide what to send in it, and how.
      $links_array = [];
      foreach ($this->languageManager->getNativeLanguages() as $language) {
        $url = Url::fromUserInput($destination, ['language' => $language]);
        $links_array[$language->getId()] = [
          // We need to clone the $url object to avoid using the same one for
          // all links. When the links are rendered, options are set on the $url
          // object, so if we use the same one, they would be set for all links.
          'url' => clone $url,
          'title' => $language->getName(),
          'language' => $language,
          'attributes' => ['class' => ['language-link']],
        ];
      }

      foreach ($this->languageManager->getNativeLanguages() as $language) {
        $url = Url::fromUserInput($destination, ['language' => $language]);
        $project_link = Link::fromTextAndUrl($language->getName(), $url);
        $project_link = $project_link->toRenderable();
        $project_link['#attributes'] = array('class' => array('language_selection_page_link_' . $language->getId()));
        $links[$language->getId()] = $project_link;
      }
    }
    catch (\InvalidArgumentException $exception) {
      $destination = '<front>';
    }

    $content[] = [
      '#theme' => 'language_selection_page_content',
      '#destination' => $destination,
      '#language_links' => [
        '#theme' => 'item_list',
        '#items' => $links,
      ],
      '#cache' => [
        'contexts' => ['url']
      ],
    ];
  }

}
