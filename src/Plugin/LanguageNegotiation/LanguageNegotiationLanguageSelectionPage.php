<?php

declare(strict_types = 1);

namespace Drupal\language_selection_page\Plugin\LanguageNegotiation;

use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language from a language selection page.
 *
 * @LanguageNegotiation(
 *   weight = -4,
 *   name = @Translation("Language Selection Page"),
 *   description = @Translation("Language is set from a language selection page"),
 *   id = Drupal\language_selection_page\Plugin\LanguageNegotiation\LanguageNegotiationLanguageSelectionPage::METHOD_ID,
 *   types = {\Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE},
 *   config_route_name = "language_selection_page.negotiation_selection_page"
 * )
 */
class LanguageNegotiationLanguageSelectionPage extends LanguageNegotiationMethodBase {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-selection-page';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    // Negotiation is always "unsuccessful". We link to the possible language
    // versions in the language page itself.
    return FALSE;
  }

}
