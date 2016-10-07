<?php

namespace Drupal\language_selection_page\Controller;

use Drupal\Core\Config\Config;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
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
   * @var RouteMatchInterface $currentRouteMatch
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
   * PageController constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The route match service.
   * @param \Drupal\Core\Render\MainContent\MainContentRendererInterface $main_content_renderer
   *   The main content renderer.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   */
  public function __construct(RouteMatchInterface $current_route_match, MainContentRendererInterface $main_content_renderer, RequestStack $request_stack, LinkGeneratorInterface $link_generator) {
    $this->currentRouteMatch = $current_route_match;
    $this->mainContentRenderer = $main_content_renderer;
    $this->requestStack = $request_stack;
    $this->linkGenerator = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('main_content_renderer.html'),
      $container->get('request_stack'),
      $container->get('link_generator')
    );
  }

  /**
   * Callback: Gets the content of the Language Selection Page.
   *
   * Method used in LanguageSelectionPageController::main() and
   * LanguageSelectionPageBlock::build().
   *
   * @todo fix these:
   * TODO: Currently the method returns and array or a RedirectResponse.
   * TODO: We should rewrite in a way that it returns only one data type.
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
   *   A render array or a RedirectResponse to the frontpage.
   */
  public static function getContent(RequestStack $request_stack, LanguageManagerInterface $language_manager, LinkGeneratorInterface $link_generator, Config $config) {
    $request = $request_stack->getCurrentRequest();
    $languages = $language_manager->getLanguages();

    if ('block' != $config->get('type')) {
      if (!empty($request->getQueryString())) {
        list(, $destination) = explode('=', $request->getQueryString(), 2);
        $destination = urldecode($destination);
        if (empty($destination)) {
          // @todo what if the path is prefixed? Maybe redirect to <front> instead?
          return new RedirectResponse('/');
        }
      }
      else {
        // @todo what if the path is prefixed? Maybe redirect to <front> instead?
        return new RedirectResponse('/');
      }
    } else {
      $destination = $request->getPathInfo();
    }

    // @todo fix this -- this variable isn't used..
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
      $link = $link_generator->generate($language->getName(), $url);
      $links[$language->getId()] = $link;
    }

    return [
      '#theme' => 'language_selection_page_content',
      '#destination' => $destination,
      '#language_links' => [
        '#theme' => 'item_list',
        '#items' => $links,
      ],
    ];
  }

  /**
   * Page callback.
   */
  public function main() {
    $config = $this->config('language_selection_page.negotiation');
    $response = $this->getContent($this->requestStack, $this->languageManager(), $config);

    if ('standalone' == $config->get('type')) {
      $page = [
        '#type' => 'page',
        '#title' => $config->get('title'),
        'content' => $response,
      ];

      $response = $this->mainContentRenderer->renderResponse($page, $this->requestStack->getCurrentRequest(), $this->currentRouteMatch);
    }

    return $response;
  }

}
