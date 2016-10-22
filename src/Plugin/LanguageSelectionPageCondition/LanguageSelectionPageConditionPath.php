<?php

namespace Drupal\language_selection_page\Plugin\LanguageSelectionPageCondition;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathValidatorInterface;
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
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Constructs a LanguageSelectionPageConditionPath plugin.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_config
   *   A cache backend used to store configuration.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, RequestStack $request_stack, CurrentPathStack $current_path, RouteBuilderInterface $route_builder, CacheBackendInterface $cache_config, PathValidatorInterface $path_validator, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->aliasManager = $alias_manager;
    $this->requestStack = $request_stack;
    $this->currentPath = $current_path;
    $this->routeBuilder = $route_builder;
    $this->cacheConfig = $cache_config;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.alias_manager'),
      $container->get('request_stack'),
      $container->get('path.current'),
      $container->get('router.builder'),
      $container->get('cache.config'),
      $container->get('path.validator'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $configuration = $this->getConfiguration();
    $configuration_lsp = $this->configFactory->getEditable('language_selection_page.negotiation');
    $system_site = $this->configFactory->getEditable('system.site');

    if ($configuration['path'] == $system_site->get('page.front')) {
      $configuration_lsp->set('path', '/language_selection_page')->save();
      $system_site->set('page.front', '/node')->save();
      drupal_set_message($this->t('The Language Selection Page cannot be used as frontpage. To avoid infinite redirect loops, the language selection page path has been reset to <strong>/language_selection_page</strong> and the default frontpage setting has been reset to <strong>/node</strong>.'), 'error');
      return $this->block();
    }

    $current_path = $this->currentPath->getPath($this->requestStack->getCurrentRequest());
    $alias = $this->aliasManager->getAliasByPath($current_path);
    foreach ([$current_path, $alias] as $path) {
      if ($path === $configuration[$this->getPluginId()]) {
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
      '#field_prefix' => $base_url,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $configuration_lsp = $this->configFactory->getEditable('language_selection_page.negotiation');

    // Check for empty path.
    if ($form_state->isValueEmpty($this->getPluginId())) {
      // Set to default "/language_selection_page".
      $form_state->setValueForElement($form['conditions'][$this->getPluginId()], '/language_selection_page');
    }
    else {
      $form_state->setValueForElement($form['conditions'][$this->getPluginId()], $this->aliasManager->getPathByAlias($form_state->getValue($this->getPluginId())));
    }

    // Validate path.
    if (($value = $form_state->getValue($this->getPluginId())) && $value[0] !== '/') {
      $form_state->setErrorByName($this->getPluginId(), $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue($this->getPluginId())]));
    }

    // Check if the path already exists.
    if ($this->pathValidator->isValid($form_state->getValue($this->getPluginId())) && $form_state->getValue($this->getPluginId()) !== $configuration_lsp->get('path')) {
      $form_state->setErrorByName($this->getPluginId(), $this->t("The path '%path' is invalid.", array('%path' => $form_state->getValue($this->getPluginId()))));
    }
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
