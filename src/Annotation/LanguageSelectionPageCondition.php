<?php

namespace Drupal\language_selection_page\Annotation;

use Drupal\Core\Condition\Annotation\Condition;

/**
 * Defines a language selection page condition annotation object.
 *
 * Plugin Namespace: Plugin\LanguageSelectionPageCondition.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class LanguageSelectionPageCondition extends Condition {

  /**
   * The language selection page condition plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The default weight of the language selection page condition plugin.
   *
   * @var int
   */
  public $weight;

  /**
   * The human-readable name of the language selection page condition plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The description of the language selection page condition plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * Whether to apply the plugin to the Language selection page block.
   *
   * - TRUE if this condition plugin should be run to determine whether to
   *   redirect to a language page, as well as whether to display the block.
   * - FALSE if this condition should be run to determine whether to redirect
   *   to a language page, but not whether to display the block.
   *
   * @var bool
   *
   * @see \Drupal\language_selection_page\Plugin\Block\LanguageSelectionPageBlock::blockAccess()
   */
  public $runInBlock;

}
