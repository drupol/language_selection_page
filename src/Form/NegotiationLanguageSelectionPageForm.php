<?php

namespace Drupal\language_selection_page\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the Language Selection Page language negotiation method.
 */
class NegotiationLanguageSelectionPageForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The variable containing the conditions configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The Language Selection Page condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $languageSelectionPageConditionManager;

  /**
   * NegotiationLanguageSelectionPageForm constructor.
   *
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $plugin_manager
   *   The plugin manager.
   */
  public function __construct(ExecutableManagerInterface $plugin_manager) {
    parent::__construct($this->configFactory());
    $this->languageSelectionPageConditionManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.language_selection_page_condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_negotiation_configure_language_selection_page_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['language_selection_page.negotiation'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->config = $this->config('language_selection_page.negotiation');
    $manager = $this->languageSelectionPageConditionManager;

    foreach ($manager->getDefinitions() as $def) {
      $condition_plugin = $manager->createInstance($def['id']);
      $form_state->set(['conditions', $condition_plugin->getPluginId()], $condition_plugin);

      $condition_plugin->setConfiguration($condition_plugin->getConfiguration() + (array) $this->config->get());

      $condition_form = [];
      $condition_form['#markup'] = $condition_plugin->getDescription();
      $condition_form += $condition_plugin->buildConfigurationForm([], $form_state);

      if (!empty($condition_form[$condition_plugin->getPluginId()])) {
        $condition_form['#type'] = 'details';
        $condition_form['#open'] = TRUE;
        $condition_form['#title'] = $condition_plugin->getName();
        $condition_form['#weight'] = $condition_plugin->getWeight();
        $form['conditions'][$condition_plugin->getPluginId()] = $condition_form;
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\language_selection_page\LanguageSelectionPageConditionInterface $condition */
    foreach ($form_state->get(['conditions']) as $condition) {
      $condition->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\language_selection_page\LanguageSelectionPageConditionInterface $condition */
    foreach ($form_state->get(['conditions']) as $condition) {
      $condition->submitConfigurationForm($form, $form_state);
      if (isset($condition->getConfiguration()[$condition->getPluginId()])) {
        $this->config
          ->set($condition->getPluginId(), $condition->getConfiguration()[$condition->getPluginId()]);
      }
    }

    $this->config->save();

    /** @var \Drupal\language_selection_page\LanguageSelectionPageConditionInterface $condition */
    foreach ($form_state->get(['conditions']) as $condition) {
      $condition->postConfigSave($form, $form_state);
    }

    // Redirect to the language negotiation page on submit (previous Drupal 7
    // behavior, and intended behavior for other language negotiation settings
    // forms in Drupal 8 core).
    $form_state->setRedirect('language.negotiation');
  }

}
