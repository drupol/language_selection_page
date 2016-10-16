<?php

namespace Drupal\language_selection_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Controller for legacy route (Drupal 7 path to Drupal 8 path).
 */
class LegacyDrupal7Redirect extends ControllerBase {

  /**
   * Main callback.
   *
   * Callback for the
   * language_selection_page.negotiation_language_selection_page_legacy_redirect
   * route.
   */
  public function doRedirect() {
    return new RedirectResponse(Url::fromRoute('language_selection_page.negotiation_selection_page')->setAbsolute()->toString());
  }

}
