<?php

namespace Drupal\language_selection_page\Plugin\LanguageSelectionPageCondition;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\language\LanguageNegotiatorInterface;
use Drupal\language_selection_page\LanguageSelectionPageConditionBase;
use Drupal\language_selection_page\LanguageSelectionPageConditionInterface;
use Drupal\language_selection_page\Plugin\LanguageNegotiation\LanguageNegotiationLanguageSelectionPage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for LanguageSelectionPageConditionMethodIsValid.
 *
 * @LanguageSelectionPageCondition(
 *   id = "method_is_valid",
 *   weight = -200,
 *   name = @Translation("Method is valid"),
 *   description = @Translation("Bails out if the method is not present."),
 *   runInBlock = FALSE,
 * )
 */
class LanguageSelectionPageConditionMethodIsValid extends LanguageSelectionPageConditionBase implements LanguageSelectionPageConditionInterface {

  /**
   * The current path.
   *
   * @var LanguageNegotiatorInterface
   */
  protected $languageNegotiator;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a LanguageCookieConditionPath plugin.
   *
   * @param LanguageNegotiatorInterface $language_negotiator
   *   The language negotiator.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(LanguageNegotiatorInterface $language_negotiator, AccountInterface $current_user, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageNegotiator = $language_negotiator;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('language_negotiator'),
      $container->get('current_user'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $this->languageNegotiator->setCurrentUser($this->currentUser);
    $methods = $this->languageNegotiator->getNegotiationMethods(LanguageInterface::TYPE_INTERFACE);

    if (!isset($methods[LanguageNegotiationLanguageSelectionPage::METHOD_ID])) {
      return $this->block();
    }

    return $this->pass();
  }

}
