<?php

namespace Drupal\language_selection_page\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
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
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;


  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $configFactory;

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
   * PageController constructor.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, ExecutableManagerInterface $plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->languageSelectionPageConditionManager = $plugin_manager;
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
      $container->get('plugin.manager.language_selection_page_condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->config('language_selection_page.negotiation');

    $content = NULL;
    if ('block' == $config->get('type')) {
      $content = LanguageSelectionPageController::getContent($this->requestStack, $this->languageManager(), $this->linkGenerator(), $config);
    }

    return is_array($content) ? $content : NULL;
  }

  /**
   * Returns the language manager service.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   */
  protected function languageManager() {
    if (!$this->languageManager) {
      $this->languageManager = $this->container()->get('language_manager');
    }
    return $this->languageManager;
  }

  /**
   * Returns the link generator service.
   *
   * @return \Drupal\Core\Utility\LinkGeneratorInterface
   *   The link generator.
   */
  protected function linkGenerator() {
    if (!$this->linkGenerator) {
      $this->linkGenerator = $this->container()->get('link_generator');
    }
    return $this->linkGenerator;
  }

  /**
   * Returns the service container.
   *
   * This method is marked private to prevent sub-classes from retrieving
   * services from the container through it. Instead,
   * \Drupal\Core\DependencyInjection\ContainerInjectionInterface should be used
   * for injecting services.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The service container.
   */
  private function container() {
    return \Drupal::getContainer();
  }

  /**
   * Retrieves a configuration object.
   *
   * This is the main entry point to the configuration API.
   * Calling @code $this->config('book.admin') @endcode will return a
   * configuration object in which the book module can store its
   * administrative settings.
   *
   * @param string $name
   *   The name of the configuration object to retrieve. The name corresponds to
   *   a configuration file. For @code \Drupal::config('book.admin') @endcode,
   *   the config object returned will contain the contents of book.admin
   *   configuration file.
   *
   * @return \Drupal\Core\Config\Config
   *   A configuration object.
   */
  protected function config($name) {
    if (!$this->configFactory) {
      $this->configFactory = $this->container()->get('config.factory');
    }
    return $this->configFactory->get($name);
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $config = $this->config('language_selection_page.negotiation');
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
