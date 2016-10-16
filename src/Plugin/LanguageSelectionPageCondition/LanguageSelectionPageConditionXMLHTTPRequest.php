<?php

namespace Drupal\language_selection_page\Plugin\LanguageSelectionPageCondition;

use Drupal\language_selection_page\LanguageSelectionPageConditionBase;
use Drupal\language_selection_page\LanguageSelectionPageConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class for LanguageSelectionPageConditionXMLHTTPRequest.
 *
 * @LanguageSelectionPageCondition(
 *   id = "xml_http_request",
 *   weight = -110,
 *   name = @Translation("XML HTTP Request"),
 *   description = @Translation("Bails out when the request is an AJAX request."),
 *   runInBlock = FALSE,
 * )
 */
class LanguageSelectionPageConditionXMLHTTPRequest extends LanguageSelectionPageConditionBase implements LanguageSelectionPageConditionInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a LanguageSelectionPageConditionPath plugin.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(RequestStack $request_stack, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('request_stack'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if ($this->requestStack->getCurrentRequest()->isXmlHttpRequest()) {
      return $this->block();
    }

    return $this->pass();
  }

}
