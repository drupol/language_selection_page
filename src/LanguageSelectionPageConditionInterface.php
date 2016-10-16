<?php

namespace Drupal\language_selection_page;

use Drupal\Core\Condition\ConditionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface LanguageSelectionPageConditionInterface.
 */
interface LanguageSelectionPageConditionInterface extends ConditionInterface {

  /**
   * Wrapper function that returns FALSE.
   *
   * @return bool
   *   Return FALSE
   */
  public function block();

  /**
   * Wrapper function that returns FALSE.
   *
   * @return bool
   *   Return TRUE
   */
  public function pass();

  /**
   * Returns the name of the plugin.
   *
   * If the name is not set, returns its ID.
   *
   * @return string
   *   The name of the plugin.
   */
  public function getName();

  /**
   * Returns the description of the plugin.
   *
   * If the description is not set, returns NULL.
   *
   * @return string|null
   *   The description of the plugin.
   */
  public function getDescription();

  /**
   * Returns the weight of the plugin.
   *
   * If the weight is not set, returns 0.
   *
   * @return int
   *   The weight of the plugin.
   */
  public function getWeight();

  /**
   * Set the weight of the plugin.
   *
   * @param int $weight
   *   The plugin's weight.
   *
   * @return $this
   *   Returns itself.
   */
  public function setWeight($weight);

  /**
   * Post config save method.
   *
   * Method that gets triggered when the configuration of the form
   * has been saved.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState object.
   */
  public function postConfigSave(array &$form, FormStateInterface $form_state);

}
