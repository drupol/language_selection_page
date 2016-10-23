<?php

namespace Drupal\language_selection_page;

use Drupal\Core\Condition\ConditionInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for language selection page condition.
 */
abstract class LanguageSelectionPageConditionBase extends ConditionPluginBase implements LanguageSelectionPageConditionInterface, ContainerFactoryPluginInterface, ConditionInterface {

  /**
   * The condition's weight, order of execution.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function block() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function pass() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // This should return a summary but it's not used in our case.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration[$this->getPluginId()] = $form_state->getValue($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $definition = $this->getPluginDefinition();
    return !empty($definition['name']) ? $definition['name'] : $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $definition = $this->getPluginDefinition();
    return !empty($definition['description']) ? $definition['description'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return !empty($this->weight) ? $this->weight : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return $this->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function postConfigSave(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function alterPageContent(array &$content = array(), $destination = '<front>') {

  }

  /**
   * {@inheritdoc}
   */
  public function alterPageResponse(&$content = array()) {

  }

  /**
   * {@inheritdoc}
   */
  public function getDestination($destination) {
    return $destination;
  }

}
