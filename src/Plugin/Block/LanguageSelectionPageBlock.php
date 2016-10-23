<?php

namespace Drupal\language_selection_page\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\language_selection_page\Controller\LanguageSelectionPageController;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The Language Selection Page condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $languageSelectionPageConditionManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The page controller.
   *
   * @var \Drupal\language_selection_page\Controller\LanguageSelectionPageController
   */
  protected $pageController;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\language_selection_page\Controller\LanguageSelectionPageController $page_controller
   *   The page controller.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, ExecutableManagerInterface $plugin_manager, ConfigFactoryInterface $config_factory, LanguageSelectionPageController $page_controller) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageSelectionPageConditionManager = $plugin_manager;
    $this->configFactory = $config_factory;
    $this->pageController = $page_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.language_selection_page_condition'),
      $container->get('config.factory'),
      $container->get('language_selection_page_controller')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory->get('language_selection_page.negotiation');
    $content = NULL;

    if ('block' == $config->get('type')) {
      $destination = $this->pageController->getDestination();
      $content = $this->pageController->getPageContent($destination);
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
      /** @var \Drupal\Core\Executable\ExecutableInterface $condition_plugin */
      $condition_plugin = $manager->createInstance($def['id'], $config->get());
      if (!$manager->execute($condition_plugin)) {
        return AccessResult::forbidden();
      }
    }

    return AccessResult::allowed();
  }

}
