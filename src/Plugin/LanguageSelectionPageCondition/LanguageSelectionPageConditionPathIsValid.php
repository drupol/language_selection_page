<?php

namespace Drupal\language_selection_page\Plugin\LanguageSelectionPageCondition;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\language_selection_page\LanguageSelectionPageConditionBase;
use Drupal\language_selection_page\LanguageSelectionPageConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class for LanguageSelectionPageConditionPathIsValid.
 *
 * @LanguageSelectionPageCondition(
 *   id = "path_is_valid",
 *   weight = -110,
 *   name = @Translation("Valid path"),
 *   description = @Translation("Bails out if the path is not valid."),
 *   runInBlock = TRUE,
 * )
 */
class LanguageSelectionPageConditionPathIsValid extends LanguageSelectionPageConditionBase implements LanguageSelectionPageConditionInterface {

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
   * The path validator.
   *
   * @var PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Constructs a LanguageSelectionPageConditionPath plugin.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param PathValidatorInterface $path_validator
   *   The path validator.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(RequestStack $request_stack, CurrentPathStack $current_path, PathValidatorInterface $path_validator, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->currentPath = $current_path;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('request_stack'),
      $container->get('path.current'),
      $container->get('path.validator'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $path = $this->currentPath->getPath($this->requestStack->getCurrentRequest());

    if ($this->pathValidator->isValid($path)) {
      return $this->pass();
    }

    return $this->block();
  }

}
