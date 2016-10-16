<?php

namespace Drupal\language_selection_page\Plugin\LanguageSelectionPageCondition;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\language_selection_page\LanguageSelectionPageConditionBase;
use Drupal\language_selection_page\LanguageSelectionPageConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class for the Path condition plugin.
 *
 * @LanguageSelectionPageCondition(
 *   id = "path",
 *   weight = -100,
 *   name = @Translation("Language selection page path"),
 *   description = @Translation("Set the path of the language selection page."),
 *   runInBlock = TRUE,
 * )
 */
class LanguageSelectionPageConditionPath extends LanguageSelectionPageConditionBase implements LanguageSelectionPageConditionInterface {

  /**
   * An alias manager to find the alias for the current system path.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The route builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * The instantiated Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheConfig;

  /**
   * Constructs a LanguageSelectionPageConditionPath plugin.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_config
   *   A cache backend used to store configuration.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(AliasManagerInterface $alias_manager, RequestStack $request_stack, CurrentPathStack $current_path, RouteBuilderInterface $route_builder, CacheBackendInterface $cache_config, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->aliasManager = $alias_manager;
    $this->requestStack = $request_stack;
    $this->currentPath = $current_path;
    $this->routeBuilder = $route_builder;
    $this->cacheConfig = $cache_config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('path.alias_manager'),
      $container->get('request_stack'),
      $container->get('path.current'),
      $container->get('router.builder'),
      $container->get('cache.config'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $current_path = $this->currentPath->getPath($this->requestStack->getCurrentRequest());
    $alias = $this->aliasManager->getAliasByPath($current_path);
    foreach ([$current_path, $alias] as $path) {
      $path_elements = explode('/', trim($path, '/'));

      if ($path_elements[0] === $this->configuration[$this->getPluginId()]) {
        return $this->block();
      }
    }

    return $this->pass();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $form[$this->getPluginId()] = [
      '#type' => 'textfield',
      '#default_value' => $this->configuration[$this->getPluginId()],
      '#description' => t('The path of the page displaying the Language Selection Page'),
      '#required' => TRUE,
      '#size' => 40,
      '#field_prefix' => $base_url . '/',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Flush only if there is a change in the path.
    if ($this->configuration[$this->getPluginId()] != $form_state->getValue($this->getPluginId())) {
      $this->routeBuilder->setRebuildNeeded();
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function postConfigSave(array &$form, FormStateInterface $form_state) {
    $this->routeBuilder->rebuildIfNeeded();
  }

}
