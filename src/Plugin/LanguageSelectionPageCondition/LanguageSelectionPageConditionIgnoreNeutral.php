<?php

namespace Drupal\language_selection_page\Plugin\LanguageSelectionPageCondition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language_selection_page\LanguageSelectionPageConditionBase;
use Drupal\language_selection_page\LanguageSelectionPageConditionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for the Ignore Language Neutral plugin.
 *
 * @LanguageSelectionPageCondition(
 *   id = "ignore_neutral",
 *   weight = -40,
 *   name = @Translation("Ignore untranslatable (language neutral) entities"),
 *   description = @Translation("Ignore untranslatable entities (such as entities with language set to <em>Not specified</em> or <em>Not applicable</em>, or with content types that are not translatable)"),
 * )
 */
class LanguageSelectionPageConditionIgnoreNeutral extends LanguageSelectionPageConditionBase implements LanguageSelectionPageConditionInterface {

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
    // Check if the "ignore language neutral" option is checked.
    // If so, we will check if the entity is translatable, so that pages for
    // entities with default entity language set to LANGCODE_NOT_APPLICABLE or
    // LANGCODE_NOT_SPECIFIED, or where the content type is not translatable,
    // are ignored.
    if ($this->configuration[$this->getPluginId()]) {
      // Get the first entity from the route.
      foreach (\Drupal::routeMatch()->getParameters() as $parameter) {
        if ($parameter instanceof ContentEntityInterface) {
          $entity = $parameter;
          if (!$entity->isTranslatable()) {
            return $this->block();
          }
        }
      }
    }

    return $this->pass();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[$this->getPluginId()] = [
      '#title' => $this->t('Ignore untranslatable (language neutral) entities.'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration[$this->getPluginId()],
      '#description' => $this->t('Do not redirect to the language selection page if the entity on the page being viewed is not translatable (such as when it is language neutral, or if the content type it belongs to is not translatable).'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $form_state->set($this->getPluginId(), (bool) $form_state->get($this->getPluginId()));
  }

}
