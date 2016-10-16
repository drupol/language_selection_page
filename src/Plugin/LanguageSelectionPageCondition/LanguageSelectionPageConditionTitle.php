<?php

namespace Drupal\language_selection_page\Plugin\LanguageSelectionPageCondition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\language_selection_page\LanguageSelectionPageConditionBase;
use Drupal\language_selection_page\LanguageSelectionPageConditionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LanguageSelectionPageConditionTitle.
 *
 * @LanguageSelectionPageCondition(
 *   id = "title",
 *   weight = -200,
 *   name = @Translation("Language selection page title"),
 *   description = @Translation("Set the title of the language selection page."),
 *   runInBlock = FALSE,
 * )
 */
class LanguageSelectionPageConditionTitle extends LanguageSelectionPageConditionBase implements LanguageSelectionPageConditionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return $this->pass();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[$this->getPluginId()] = [
      '#type' => 'textfield',
      '#default_value' => $this->configuration[$this->getPluginId()],
      '#description' => t('The title of the page.'),
      '#required' => TRUE,
      '#size' => 40,
    ];

    return $form;
  }

}
