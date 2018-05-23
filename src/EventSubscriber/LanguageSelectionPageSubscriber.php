<?php

namespace Drupal\language_selection_page\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\language\LanguageNegotiatorInterface;
use Drupal\language_selection_page\Plugin\LanguageNegotiation\LanguageNegotiationLanguageSelectionPage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSelected;

/**
 * Provides a LanguageSelectionPageSubscriber.
 */
class LanguageSelectionPageSubscriber implements EventSubscriberInterface {

  /**
   * The event.
   *
   * @var FilterResponseEvent
   */
  protected $event;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language negotiator.
   *
   * @var \Drupal\language\LanguageNegotiatorInterface
   */
  protected $languageNegotiator;

  /**
   * The language path processor.
   *
   * @var \Drupal\language\HttpKernel\PathProcessorLanguage
   */
  protected $pathProcessorLanguage;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The Language Selection Page condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $languageSelectionPageConditionManager;

  /**
   * Constructs a new class object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\language\LanguageNegotiatorInterface $language_negotiator
   *   The language negotiator.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $plugin_manager
   *   The language selection page condition plugin manager.
   */
  public function __construct(LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, LanguageNegotiatorInterface $language_negotiator, CurrentPathStack $current_path, ExecutableManagerInterface $plugin_manager) {
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->languageNegotiator = $language_negotiator;
    $this->currentPath = $current_path;
    $this->languageSelectionPageConditionManager = $plugin_manager;
  }

  /**
   * Callback helper.
   *
   * @return array|bool
   *   The language to use, or FALSE.
   */
  protected function getLanguage() {
    // Get all methods available for the user interface language type.
    $methods = $this->languageNegotiator->getNegotiationMethods(LanguageInterface::TYPE_INTERFACE);

    // We ignore this language method or else it will always return a language.
    unset($methods[LanguageNegotiationSelected::METHOD_ID]);
    uasort($methods, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    foreach ($methods as $method_id => $method_definition) {
      // Do not consider methods with a lower priority than the language
      // selection page method, nor the language selection page method itself.
      if ($method_id == LanguageNegotiationLanguageSelectionPage::METHOD_ID) {
        return FALSE;
      }
      $lang = $this->languageNegotiator->getNegotiationMethodInstance($method_id)->getLangcode($this->event->getRequest());
      if ($lang) {
        return $lang;
      }
    }

    // If no other language was found, use the default one.
    return $this->languageManager->getDefaultLanguage()->getId();
  }

  /**
   * Event callback.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event object.
   *
   * @return bool
   *   Returns FALSE.
   */
  public function redirectToLanguageSelectionPage(FilterResponseEvent $event) {
    $this->event = $event;
    $config = $this->configFactory->get('language_selection_page.negotiation');

    $manager = $this->languageSelectionPageConditionManager;

    foreach ($manager->getDefinitions() as $def) {
      /** @var ExecutableInterface $condition_plugin */
      $condition_plugin = $manager->createInstance($def['id'], $config->get());
      if (!$manager->execute($condition_plugin)) {
        return FALSE;
      }
    }

    if (!$this->getLanguage()) {
      $request = $this->event->getRequest();
      $url = sprintf('%s?destination=%s', $request->getBasePath().$config->get('path'), $this->currentPath->getPath($request));
      $response = new RedirectResponse($url);

      $event->setResponse($response);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // You can set the order of execution of this event callback in the array.
    // Find the order of execution by doing this in the Drupal Root:
    // grep "$events[KernelEvents::RESPONSE][]" . -R | grep -v 'Test'
    // The value is currently set to -50, feel free to adjust if needed.
    $events[KernelEvents::RESPONSE][] = array('redirectToLanguageSelectionPage', -50);
    return $events;
  }

}
