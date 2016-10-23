<?php

namespace Drupal\Tests\language_selection_page\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the condition plugins work.
 *
 * @group language_selection_page
 */
class LanguageSelectionPageConditionTest extends BrowserTestBase {

  /**
   * Text to assert for to determine if we are on the Language Selection Page.
   */
  const LANGUAGE_SELECTION_PAGE_TEXT = 'This page is the default page of the module Language Selection Page';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'language_selection_page',
    'locale',
    'content_translation',
  ];

  /**
   * Use the standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Hold the original configuration of LSP.
   *
   * @var array
   */
  protected $configOriginal;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);
    // Create FR.
    $this->drupalPostForm('admin/config/regional/language/add', [
      'predefined_langcode' => 'fr',
    ], 'Add language');
    // Set prefixes to en and fr.
    $this->drupalPostForm('admin/config/regional/language/detection/url', [
      'prefix[en]' => 'en',
      'prefix[fr]' => 'fr',
    ], 'Save configuration');
    // Set up URL and language selection page methods.
    $this->drupalPostForm('admin/config/regional/language/detection', [
      'language_interface[enabled][language-selection-page]' => 1,
      'language_interface[enabled][language-url]' => 1,
    ], 'Save settings');
    // Turn on content translation for pages.
    $this->drupalPostform('admin/structure/types/manage/page', ['language_configuration[content_translation]' => 1], 'Save content type');

    $this->configOriginal = $this->config('language_selection_page.negotiation')->get();
  }

  /**
   * Reset the configuration to the initial state.
   */
  protected function resetConfiguration() {
    $this->config('language_selection_page.negotiation')
      ->setData($this->configOriginal)
      ->save();
  }

  /**
   * Assert that the language selection page is loaded.
   */
  protected function assertLanguageSelectionPageLoaded() {
    $this->assertSession()->pageTextContains(self::LANGUAGE_SELECTION_PAGE_TEXT);
  }

  /**
   * Assert that the language selection page is not loaded.
   */
  protected function assertLanguageSelectionPageNotLoaded() {
    $this->assertSession()->pageTextNotContains(self::LANGUAGE_SELECTION_PAGE_TEXT);
  }

  /**
   * Test the "language prefixes" condition.
   */
  public function testEnabledLanguages() {
    $node = $this->drupalCreateNode();
    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageLoaded();

    // Set prefixes to fr only.
    $this->drupalPostForm('admin/config/regional/language/detection/url', [
      'prefix[en]' => '',
      'prefix[fr]' => 'fr',
    ], 'Save configuration');
    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageNotLoaded();
    $this->drupalGet('admin/reports/status');
    // Look for "You should add a path prefix to English language if you want
    // to have it enabled in the Language Selection Page.".
    $this->assertSession()->pageTextContains('language if you want to have it enabled in the Language Selection Page');
    $this->drupalPostForm('admin/config/regional/language/detection/url', [
      'prefix[en]' => 'en',
      'prefix[fr]' => 'fr',
    ], 'Save configuration');
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextNotContains('language if you want to have it enabled in the Language Selection Page');

    $this->resetConfiguration();
  }

  /**
   * Test the "ignore language neutral" condition.
   */
  public function testIgnoreLanguageNeutral() {
    // Enable ignore language paths.
    $this->drupalPostForm('admin/config/regional/language/detection/language_selection_page', ['ignore_neutral' => 1], 'Save configuration');

    // Create translatable node.
    $translatable_node1 = $this->drupalCreateNode(['langcode' => 'fr']);
    $this->drupalGet('node/' . $translatable_node1->id());
    $this->assertLanguageSelectionPageLoaded();

    // Create untranslatable node.
    $untranslatable_node1 = $this->drupalCreateNode(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED]);
    $this->drupalGet('node/' . $untranslatable_node1->id());
    $this->assertLanguageSelectionPageNotLoaded();

    // Create untranslatable node.
    $untranslatable_node1 = $this->drupalCreateNode(['langcode' => LanguageInterface::LANGCODE_NOT_APPLICABLE]);
    $this->drupalGet('node/' . $untranslatable_node1->id());
    $this->assertLanguageSelectionPageNotLoaded();

    // Turn off translatability of the content type.
    $this->drupalPostform('admin/structure/types/manage/page', ['language_configuration[content_translation]' => 0], 'Save content type');
    $this->drupalGet('node/' . $translatable_node1->id());
    // Assert that we don't redirect anymore.
    $this->assertLanguageSelectionPageNotLoaded();
    // Turn on translatability of the content type.
    $this->drupalPostform('admin/structure/types/manage/page', ['language_configuration[content_translation]' => 1], 'Save content type');

    // Disable ignore language paths.
    $this->drupalPostForm('admin/config/regional/language/detection/language_selection_page', ['ignore_neutral' => 0], 'Save configuration');
    $this->drupalGet('node/' . $untranslatable_node1->id());
    $this->assertLanguageSelectionPageLoaded();

    $this->resetConfiguration();
  }

  /**
   * Test the "page title" condition.
   *
   * Note: this is not really a "condition", just a configurable option.
   */
  public function testPageTitle() {
    $title = 'adJKFD#@H5864193177';
    $this->drupalPostForm('admin/config/regional/language/detection/language_selection_page', ['title' => $title], 'Save configuration');
    $node = $this->drupalCreateNode();

    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageLoaded();
    $this->assertSession()->responseContains('<title>' . $title);

    $this->resetConfiguration();
  }

  /**
   * Test the "Blacklisted paths" condition.
   */
  public function testBlackListedPaths() {
    $this->drupalGet('admin/config/regional/language/detection/language_selection_page');
    $this->assertSession()->responseContains('/node/add/*');
    $this->assertSession()->responseContains('/node/*/edit');
    $node = $this->drupalCreateNode(['langcode' => 'fr']);

    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageLoaded();

    // Add node to blacklisted paths.
    $blacklisted_paths = '/admin/*' . PHP_EOL . '/node/' . $node->id();
    $this->drupalPostForm('admin/config/regional/language/detection/language_selection_page',
      [
        'blacklisted_paths' => $blacklisted_paths,
      ],
      'Save configuration');

    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageNotLoaded();

    // Add node to blacklisted paths (in the middle).
    $blacklisted_paths = '/admin/*' . PHP_EOL . '/node/' . $node->id() . PHP_EOL . '/bar';
    $this->drupalPostForm('admin/config/regional/language/detection/language_selection_page',
      [
        'blacklisted_paths' => $blacklisted_paths,
      ],
      'Save configuration');
    $this->drupalGet('node/' . $node->id());
    // @todo fix this test
    $this->assertLanguageSelectionPageNotLoaded();

    // Add string that contains node, but not node itself.
    $this->drupalPostForm('admin/config/regional/language/detection/language_selection_page', ['blacklisted_paths' => '/admin/*' . PHP_EOL . '/node/' . $node->id() . '/foobar' . PHP_EOL . '/bar'], 'Save configuration');
    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageLoaded();

    // Add string that starts with node, but not node itself.
    $this->drupalPostForm('admin/config/regional/language/detection/language_selection_page', ['blacklisted_paths' => '/admin/*' . PHP_EOL . '/node/' . $node->id() . '/foobar'], 'Save configuration');
    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageLoaded();

    // Test front page.
    $this->drupalPostForm('admin/config/regional/language/detection/language_selection_page', ['blacklisted_paths' => '/admin/*'], 'Save configuration');
    $this->drupalGet('<front>');
    $this->assertLanguageSelectionPageLoaded();

    $this->drupalPostForm('en/admin/config/regional/language/detection/language_selection_page', ['blacklisted_paths' => '/admin/*' . PHP_EOL . '<front>'], 'Save configuration');
    $this->drupalGet('<front>');
    $this->assertLanguageSelectionPageNotLoaded();

    $this->resetConfiguration();
  }

  /**
   * Test the "path" condition.
   */
  public function testPath() {
    $node = $this->drupalCreateNode();
    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageLoaded();

    $this->drupalPostForm('admin/config/regional/language/detection/language_selection_page', ['path' => '/test'], 'Save configuration');
    // @todo uncomment and fix
    /*
    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageLoaded();
    $this->assertSession()->addressEquals('/test');

    $this->drupalPostForm('admin/config/search/path/add', [
    'langcode' => 'und',
    'source' => '/node/' . $node->id(),
    'alias' => '/test',
    ], 'Save');

    // @todo decide what should happen here
    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageNotLoaded();
     */

    $this->resetConfiguration();
  }

  /**
   * Test the "xml_http_request" condition.
   */
  public function testAjax() {
    $node = $this->drupalCreateNode();
    $headers = [];
    $this->drupalGet('node/' . $node->id(), array(), $headers);
    $this->assertLanguageSelectionPageLoaded();
    $headers['X-Requested-With'] = 'XMLHttpRequest';
    $this->drupalGet('node/' . $node->id(), array(), $headers);
    // @todo fix this test.
    $this->assertLanguageSelectionPageNotLoaded();

    $this->resetConfiguration();
  }

  /**
   * Test that the language selection block works as intended.
   */
  public function testType() {
    $node = $this->drupalCreateNode();
    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageLoaded();

    $this->drupalPostForm('admin/config/regional/language/detection/language_selection_page', ['type' => 'block'], 'Save configuration');
    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageNotLoaded();
    $this->assertSession()->pageTextNotContains('Language Selection Page block');

    $this->drupalPostForm('admin/structure/block/add/language-selection-page/bartik', ['region' => 'content'], 'Save block');
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('Language Selection Page block');
    $this->assertLanguageSelectionPageLoaded();

    // Ensure we are on a blacklisted path.
    $blacklisted_paths = implode(PHP_EOL, [
      '/admin',
      '/admin/*',
      '/admin*',
      '/node/' . $node->id(),
    ]);
    $this->drupalPostForm('admin/config/regional/language/detection/language_selection_page', ['blacklisted_paths' => $blacklisted_paths], 'Save configuration');
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextNotContains('Language Selection Page block');
    $this->assertLanguageSelectionPageNotLoaded();
    $this->resetConfiguration();

    // Test template only.
    $this->drupalPostForm('en/admin/config/regional/language/detection/language_selection_page', ['type' => 'standalone'], 'Save configuration');
    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageLoaded();
    $this->assertSession()->responseNotContains('<h2>Search</h2>');

    // Test template in theme.
    $this->drupalPostForm('en/admin/config/regional/language/detection/language_selection_page', ['type' => 'embedded'], 'Save configuration');
    $this->drupalGet('node/' . $node->id());
    $this->assertLanguageSelectionPageLoaded();
    $this->assertSession()->responseContains('<h2>Search</h2>');

    $this->resetConfiguration();
  }

}
