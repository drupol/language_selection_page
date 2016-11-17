<?php

namespace Drupal\language_selection_page\Plugin\LanguageSelectionPageCondition;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\language_selection_page\LanguageSelectionPageConditionBase;
use Drupal\language_selection_page\LanguageSelectionPageConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class for the Type condition plugin.
 *
 * @LanguageSelectionPageCondition(
 *   id = "type",
 *   weight = -90,
 *   name = @Translation("Type of operating mode and display"),
 *   description = @Translation("Select the operating mode and display."),
 *   runInBlock = FALSE,
 * )
 */
class LanguageSelectionPageConditionType extends LanguageSelectionPageConditionBase implements LanguageSelectionPageConditionInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The main content renderer.
   *
   * @var \Drupal\Core\Render\MainContent\MainContentRendererInterface
   */
  protected $mainContentRenderer;

  /**
   * Constructs a LanguageSelectionPageConditionType plugin.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The route match service.
   * @param \Drupal\Core\Render\MainContent\MainContentRendererInterface $main_content_renderer
   *   The main content renderer service.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, RouteMatchInterface $current_route_match, MainContentRendererInterface $main_content_renderer, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
    $this->currentRouteMatch = $current_route_match;
    $this->mainContentRenderer = $main_content_renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('main_content_renderer.html'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Do not return any language if we use Drupal's block method
    // to display the redirection.
    // Be aware that this will automatically assign the default language.
    if ('block' == $this->configuration[$this->getPluginId()]) {
      return $this->block();
    }

    return $this->pass();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[$this->getPluginId()] = [
      '#type' => 'select',
      '#multiple' => FALSE,
      '#default_value' => $this->configuration[$this->getPluginId()],
      '#options' => [
        'standalone' => 'Standalone',
        'embedded' => 'Embedded',
        'block' => 'Block',
      ],
      '#description' => $this->t(
        '<ul>
         <li><b>Standalone - Template only</b>: Display the Language Selection Page template only.</li>
         <li><b>Embedded - Template in theme</b>: Insert the Language Selection Page body as <i>{{ content }}</i> in the page template for the current theme.</li>
         <li><b>Block - In a Drupal block</b>: Insert the Language Selection Page in a block <em>Language Selection Block</em>.</li>
       </ul>'
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterPageContent(array &$content = array(), $destination = '<front>') {
    // TODO: update this using the config passed to the plugin.
    $config = $this->configFactory->get('language_selection_page.negotiation');

    // Render the page if we have an array in $content instead of a
    // RedirectResponse. Otherwise, redirect the user.
    if ('standalone' == $config->get('type') && !$content instanceof RedirectResponse) {
      $content = [
        '#type' => 'page',
        '#title' => $config->get('title'),
        'content' => $content,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDestination($destination) {
    // TODO: update this using the config passed to the plugin.
    $config = $this->configFactory->get('language_selection_page.negotiation');
    $request = $this->requestStack->getCurrentRequest();

    // If we display the LSP on a page, we must check
    // if the destination parameter is correctly set.
    if ('block' != $config->get('type')) {
      if (!empty($request->getQueryString())) {
        list(, $destination) = explode('=', $request->getQueryString(), 2);
        $destination = urldecode($destination);
      }
    }
    else {
      $destination = $request->getPathInfo();
    }

    return $destination;
  }

}
