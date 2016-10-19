<?php

namespace Drupal\language_selection_page\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\language_selection_page\Controller\LanguageSelectionPageController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a Language Selection Page block.
 *
 * @Block(
 *   id = "language-selection-page",
 *   admin_label = @Translation("Language Selection Page block"),
 *   category = @Translation("Block"),
 * )
 */
class LanguageSelectionPageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The Language Selection Page condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $languageSelectionPageConditionManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * LanguageSelectionPageBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $plugin_manager
   *   The language selection page condition plugin manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, ExecutableManagerInterface $plugin_manager, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, LinkGeneratorInterface $link_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->languageSelectionPageConditionManager = $plugin_manager;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->linkGenerator = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('plugin.manager.language_selection_page_condition'),
      $container->get('language_manager'),
      $container->get('config_factory'),
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory->get('language_selection_page.negotiation');
    $content = NULL;

    if ('block' == $config->get('type')) {
      $content = LanguageSelectionPageController::getContent($this->requestStack, $this->languageManager, $this->linkGenerator, $config);
    }

    return is_array($content) ? $content : NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $config = $this->configFactory->get('language_selection_page.negotiation');
    $manager = $this->languageSelectionPageConditionManager;

    $defs = array_filter($manager->getDefinitions(), function ($value) {
      return isset($value['runInBlock']) && $value['runInBlock'];
    });

    foreach ($defs as $def) {
      /** @var ExecutableInterface $condition_plugin */
      $condition_plugin = $manager->createInstance($def['id'], $config->get());
      if (!$manager->execute($condition_plugin)) {
        return AccessResult::forbidden();
      }
    }

    return AccessResult::allowed();
  }

}
