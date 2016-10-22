<?php

namespace Drupal\language_selection_page\Controller;

use Drupal\Core\Config\Config;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class LanguageSelectionPageController.
 */
class LanguageSelectionPageController extends ControllerBase {

  /**
   * The route match service.
   *
   * @var RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The main content renderer.
   *
   * @var \Drupal\Core\Render\MainContent\MainContentRendererInterface
   */
  protected $mainContentRenderer;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The Language Selection Page condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected static $languageSelectionPageConditionManager;

  /**
   * PageController constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The route match service.
   * @param \Drupal\Core\Render\MainContent\MainContentRendererInterface $main_content_renderer
   *   The main content renderer.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $plugin_manager
   *   The language selection page condition plugin manager.
   */
  public function __construct(RouteMatchInterface $current_route_match, MainContentRendererInterface $main_content_renderer, RequestStack $request_stack, LinkGeneratorInterface $link_generator, ExecutableManagerInterface $plugin_manager) {
    $this->currentRouteMatch = $current_route_match;
    $this->mainContentRenderer = $main_content_renderer;
    $this->requestStack = $request_stack;
    $this->linkGenerator = $link_generator;
    self::$languageSelectionPageConditionManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('main_content_renderer.html'),
      $container->get('request_stack'),
      $container->get('link_generator'),
      $container->get('plugin.manager.language_selection_page_condition')
    );
  }

  /**
   * Callback: Gets the content of the Language Selection Page.
   *
   * Method used in LanguageSelectionPageController::main().
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   * @param \Drupal\Core\Config\Config $config
   *   The configuration.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array or a RedirectResponse.
   */
  public static function getContent(RequestStack $request_stack, LanguageManagerInterface $language_manager, LinkGeneratorInterface $link_generator, Config $config) {
    $request = $request_stack->getCurrentRequest();
    $languages = $language_manager->getLanguages();

    // If we display the LSP on a page, we must check
    // if the destination parameter is correctly set.
    if ('block' != $config->get('type')) {
      if (!empty($request->getQueryString())) {
        list(, $destination) = explode('=', $request->getQueryString(), 2);
        $destination = urldecode($destination);
        // If the destination parameter exists and is empty,
        // redirect the user to the front page.
        if (empty($destination)) {
          return new RedirectResponse(Url::fromRoute('<front>')->setAbsolute()->toString());
        }
      }
      else {
        // If the query string containing the destination parameter is empty,
        // redirect the user to the front page.
        return new RedirectResponse(Url::fromRoute('<front>')->setAbsolute()->toString());
      }
    }
    else {
      $destination = $request->getPathInfo();
    }

    // $destination is set, now check against the LSP configuration.
    // If the path is set to $destination, redirect the user to the
    // front page to avoid useless loops.
    if (trim($destination, '/') == trim($config->get('path'), '/')) {
      return new RedirectResponse(Url::fromRoute('<front>')->setAbsolute()->toString());
    }

    // As we are generating a URL from user input, we need to catch any
    // exceptions thrown by invalid paths.
    try {
      // TODO: This variable will be used in the template.
      // TODO: We still have to decide what to send in it, and how.
      $links_array = [];
      foreach ($language_manager->getNativeLanguages() as $language) {
        $url = Url::fromUserInput($destination, ['language' => $language]);
        $links_array[$language->getId()] = [
          // We need to clone the $url object to avoid using the same one for all
          // links. When the links are rendered, options are set on the $url
          // object, so if we use the same one, they would be set for all links.
          'url' => clone $url,
          'title' => $language->getName(),
          'language' => $language,
          'attributes' => ['class' => ['language-link']],
        ];
      }

      $links = [];
      foreach ($languages as $language) {
        $url = Url::fromUserInput($destination, ['language' => $language]);
        $project_link = Link::fromTextAndUrl($language->getName(), $url);
        $project_link = $project_link->toRenderable();
        $project_link['#attributes'] = array('class' => array('language_selection_page_link_' . $language->getId()));
        $links[$language->getId()] = $project_link;
      }
    }
    catch (\InvalidArgumentException $exception) {
      return new RedirectResponse(Url::fromRoute('<front>')->setAbsolute()->toString());
    }

    $content = [
      [
        '#theme' => 'language_selection_page_content',
        '#destination' => $destination,
        '#language_links' => [
          '#theme' => 'item_list',
          '#items' => $links,
        ],
      ],
    ];

    // Alter the render array.
    $manager = self::getPluginManager();
    foreach ($manager->getDefinitions() as $def) {
      $manager->createInstance($def['id'], $config->get())->alterPageContent($content);
    }

    return $content;
  }

  /**
   * Page callback.
   */
  public function main() {
    $config = $this->config('language_selection_page.negotiation');
    $response = $this->getContent($this->requestStack, $this->languageManager(), $this->linkGenerator, $config);

    // Render the page if we have an array in $response instead of a
    // RedirectResponse. Otherwise, redirect the user.
    if ('standalone' == $config->get('type') && !$response instanceof RedirectResponse) {
      $page = [
        '#type' => 'page',
        '#title' => $config->get('title'),
        'content' => $response,
      ];

      $response = $this->mainContentRenderer->renderResponse($page, $this->requestStack->getCurrentRequest(), $this->currentRouteMatch);
    }

    return $response;
  }

  /**
   * Get the plugin manager.
   *
   * @return \Drupal\Core\Executable\ExecutableManagerInterface
   *   The Language Selection Page plugin manager.
   */
  public function getPluginManager() {
    return self::$languageSelectionPageConditionManager;
  }

}
