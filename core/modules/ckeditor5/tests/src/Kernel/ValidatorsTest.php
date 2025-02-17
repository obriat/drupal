<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor5\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\editor\EditorInterface;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use Symfony\Component\Yaml\Yaml;

// cspell:ignore onhover

/**
 * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\ToolbarItemConstraintValidator
 * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\ToolbarItemDependencyConstraintValidator
 * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\EnabledConfigurablePluginsConstraintValidator
 * @covers \Drupal\ckeditor5\Plugin\Editor\CKEditor5::validatePair()
 * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\FundamentalCompatibilityConstraintValidator
 * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\CKEditor5MediaAndFilterSettingsInSyncConstraintValidator
 * @group ckeditor5
 */
class ValidatorsTest extends KernelTestBase {

  use SchemaCheckTestTrait;
  use CKEditor5ValidationTestTrait;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'ckeditor5_plugin_conditions_test',
    'editor',
    'filter',
    'media',
    'media_library',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->typedConfig = $this->container->get('config.typed');
  }

  /**
   * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\ToolbarItemConstraintValidator
   * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\ToolbarItemDependencyConstraintValidator
   * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\EnabledConfigurablePluginsConstraintValidator
   * @dataProvider provider
   *
   * @param array $ckeditor5_settings
   *   The CKEditor 5 settings to test.
   * @param array $expected_violations
   *   All expected violations for the given CKEditor 5 settings, with property
   *   path as keys and message as values.
   */
  public function test(array $ckeditor5_settings, array $expected_violations) {
    // The data provider is unable to access services, so the test scenario of
    // testing with CKEditor 5's default settings is partially provided here.
    if ($ckeditor5_settings === ['__DEFAULT__']) {
      $ckeditor5_settings = \Drupal::service('plugin.manager.editor')->createInstance('ckeditor5')->getDefaultSettings();
    }

    FilterFormat::create([
      'format' => 'dummy',
      'name' => 'Dummy',
    ])->save();
    $editor = Editor::create([
      'format' => 'dummy',
      'editor' => 'ckeditor5',
      'settings' => $ckeditor5_settings,
      'image_upload' => [],
    ]);

    $typed_config = $this->typedConfig->createFromNameAndData(
      $editor->getConfigDependencyName(),
      $editor->toArray(),
    );
    $violations = $typed_config->validate();

    $actual_violations = [];
    foreach ($violations as $violation) {
      $actual_violations[$violation->getPropertyPath()] = (string) $violation->getMessage();
    }
    $this->assertSame($expected_violations, $actual_violations);

    if (empty($expected_violations)) {
      $this->assertConfigSchema(
        $this->typedConfig,
        $editor->getConfigDependencyName(),
        $typed_config->getValue()
      );
    }
  }

  /**
   * Provides a list of Text Editor config entities using CKEditor 5 to test.
   */
  public function provider(): array {
    $data = [];
    $data['CKEditor5::getDefaultSettings()'] = [
      // @see ::test()
      'settings' => ['__DEFAULT__'],
      'violations' => [],
    ];
    $data['non-existent toolbar button'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'underline',
            'bold',
            'italic',
            '-',
            'bulletedList',
            'foobar',
          ],
        ],
        'plugins' => [
          'ckeditor5_list' => [
            'reversed' => FALSE,
            'startIndex' => FALSE,
          ],
        ],
      ],
      'violations' => [
        'settings.toolbar.items.5' => 'The provided toolbar item <em class="placeholder">foobar</em> is not valid.',
      ],
    ];

    $data['missing heading plugin configuration'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'heading',
          ],
        ],
        'plugins' => [],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_heading' => 'Configuration for the enabled plugin "<em class="placeholder">Headings</em>" (<em class="placeholder">ckeditor5_heading</em>) is missing.',
      ],
    ];
    $data['missing language plugin configuration'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'textPartLanguage',
          ],
        ],
        'plugins' => [],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_language' => 'Configuration for the enabled plugin "<em class="placeholder">Language</em>" (<em class="placeholder">ckeditor5_language</em>) is missing.',
      ],
    ];
    $data['empty language plugin configuration'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'textPartLanguage',
          ],
        ],
        'plugins' => [
          'ckeditor5_language' => [],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_language' => 'Configuration for the enabled plugin "<em class="placeholder">Language</em>" (<em class="placeholder">ckeditor5_language</em>) is missing.',
      ],
    ];
    $data['valid language plugin configuration: un'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'textPartLanguage',
          ],
        ],
        'plugins' => [
          'ckeditor5_language' => [
            'language_list' => 'un',
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['valid language plugin configuration: all'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'textPartLanguage',
          ],
        ],
        'plugins' => [
          'ckeditor5_language' => [
            'language_list' => 'all',
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['invalid language plugin configuration: textPartLanguage button not enabled'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'bold',
          ],
        ],
        'plugins' => [
          'ckeditor5_language' => [
            'language_list' => 'all',
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_language.language_list' => 'Depends on <em class="placeholder">textPartLanguage</em>, which is not enabled.',
      ],
    ];
    $data['invalid language plugin configuration: invalid language_list setting'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'textPartLanguage',
          ],
        ],
        'plugins' => [
          'ckeditor5_language' => [
            'language_list' => 'foo',
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_language.language_list' => 'The value you selected is not a valid choice.',
      ],
    ];

    $data['uploadImage toolbar item condition not met: image uploads must be enabled'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'uploadImage',
          ],
        ],
        'plugins' => [],
      ],
      'violations' => [
        'settings.toolbar.items.0' => 'The <em class="placeholder">Image upload</em> toolbar item requires image uploads to be enabled.',
      ],
    ];
    $data['drupalMedia toolbar item condition not met: media filter enabled'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalMedia',
          ],
        ],
        'plugins' => [],
      ],
      'violations' => [
        'settings.toolbar.items.0' => 'The <em class="placeholder">Drupal media</em> toolbar item requires the <em class="placeholder">Embed media</em> filter to be enabled.',
      ],
    ];
    $data['fooBarConditions toolbar item condition not met: Heading and Table plugins enabled, neither are'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'fooBarConditions',
          ],
        ],
        'plugins' => [],
      ],
      'violations' => [
        'settings.toolbar.items.0' => 'The <em class="placeholder">Foo Bar (Test Plugins Condition)</em> toolbar item requires the <em class="placeholder">Headings, Table</em> plugins to be enabled.',
      ],
    ];
    $data['fooBarConditions toolbar item condition not met: Heading and Table plugins enabled, only one is'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'fooBarConditions',
            'heading',
          ],
        ],
        'plugins' => [
          'ckeditor5_heading' => [
            'enabled_headings' => [
              'heading2',
            ],
          ],
        ],
      ],
      'violations' => [
        'settings.toolbar.items.0' => 'The <em class="placeholder">Foo Bar (Test Plugins Condition)</em> toolbar item requires the <em class="placeholder">Table</em> plugin to be enabled.',
      ],
    ];
    $data['fooBarConditions toolbar item condition met: Heading and Table plugins enabled, both are'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'fooBarConditions',
            'heading',
            'insertTable',
          ],
        ],
        'plugins' => [
          'ckeditor5_heading' => [
            'enabled_headings' => [
              'heading2',
            ],
          ],
        ],
      ],
      'violations' => [],
    ];

    return $data;
  }

  /**
   * @covers \Drupal\ckeditor5\Plugin\Editor\CKEditor5::validatePair()
   * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\FundamentalCompatibilityConstraintValidator
   * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\ToolbarItemConstraintValidator
   * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\ToolbarItemDependencyConstraintValidator
   * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\EnabledConfigurablePluginsConstraintValidator
   * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\SourceEditingPreventSelfXssConstraintValidator
   * @dataProvider providerPair
   *
   * @param array $ckeditor5_settings
   *   The paired text editor's CKEditor 5 settings to test.
   * @param array $editor_image_upload_settings
   *   The paired text editor's image upload settings to test.
   * @param array $filters
   *   The paired text format's filters and filter settings.
   * @param array $expected_violations
   *   All expected violations for the pair.
   */
  public function testPair(array $ckeditor5_settings, array $editor_image_upload_settings, array $filters, array $expected_violations) {
    $text_editor = Editor::create([
      'format' => 'dummy',
      'editor' => 'ckeditor5',
      'settings' => $ckeditor5_settings,
      'image_upload' => $editor_image_upload_settings,
    ]);
    EntityViewMode::create([
      'id' => 'media.view_mode_1',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 1',
    ])->save();
    EntityViewMode::create([
      'id' => 'media.view_mode_2',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 2',
    ])->save();
    assert($text_editor instanceof EditorInterface);
    $this->assertConfigSchema(
      $this->typedConfig,
      $text_editor->getConfigDependencyName(),
      $text_editor->toArray()
    );
    $text_format = FilterFormat::create([
      'filters' => $filters,
    ]);
    assert($text_format instanceof FilterFormatInterface);

    $this->assertSame($expected_violations, $this->validatePairToViolationsArray($text_editor, $text_format, TRUE));
  }

  /**
   * Provides a list of Text Editor + Text Format pairs to test.
   */
  public function providerPair(): array {
    // cspell:ignore donk
    $data = [];
    $data['INVALID: allow_view_mode_override condition not met: filter must be configured to allow 2 or more view modes'] = [
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
        'plugins' => [
          'media_media' => [
            'allow_view_mode_override' => TRUE,
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'media_embed' => [
          'id' => 'media_embed',
          'provider' => 'media',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'default_view_mode' => 'default',
            'allowed_view_modes' => [],
            'allowed_media_types' => [],
          ],
        ],
      ],
      'violations' => [
        '' => 'The CKEditor 5 "<em class="placeholder">Media</em>" plugin\'s "<em class="placeholder">Allow the user to override the default view mode</em>" setting should be in sync with the "<em class="placeholder">Embed media</em>" filter\'s "<em class="placeholder">View modes selectable in the &quot;Edit media&quot; dialog</em>" setting: when checked, two or more view modes must be allowed by the filter.',
      ],
    ];
    $data['VALID: allow_view_mode_override condition met: filter must be configured to allow 2 or more view modes'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalMedia',
          ],
        ],
        'plugins' => [
          'media_media' => [
            'allow_view_mode_override' => TRUE,
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'media_embed' => [
          'id' => 'media_embed',
          'provider' => 'media',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'default_view_mode' => 'view_mode_1',
            'allowed_view_modes' => [
              'view_mode_1' => 'view_mode_1',
              'view_mode_2' => 'view_mode_2',
            ],
            'allowed_media_types' => [],
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['VALID: legacy format: filter_autop'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'bold',
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_autop' => [
          'id' => 'filter_autop',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [],
        ],
      ],
      'violations' => [],
    ];
    $data['VALID: legacy HTML format: filter_autop + filter_url'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'bold',
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_autop' => [
          'id' => 'filter_autop',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [],
        ],
        'filter_url' => [
          'id' => 'filter_url',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => -10,
          'settings' => [
            'filter_url_length' => 72,
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['VALID: legacy HTML format: filter_autop + filter_url (different order)'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'bold',
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_autop' => [
          'id' => 'filter_autop',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [],
        ],
        'filter_url' => [
          'id' => 'filter_url',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 10,
          'settings' => [
            'filter_url_length' => 72,
          ],
        ],
      ],
      'violations' => [],
    ];
    $restricted_html_format_filters = Yaml::parseFile(__DIR__ . '/../../../../../profiles/standard/config/install/filter.format.restricted_html.yml')['filters'];
    $data['INVALID: the default restricted_html text format'] = [
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => $restricted_html_format_filters,
      'violations' => [
        '' => 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are not allowed by the "<em class="placeholder">Limit allowed HTML tags and correct faulty HTML</em>" (<em class="placeholder">filter_html</em>) filter.',
      ],
    ];
    $data['INVALID: the modified restricted_html text format (with filter_autop and filter_url removed)'] = [
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => array_diff_key(
        $restricted_html_format_filters,
        ['filter_autop' => TRUE, 'filter_url' => TRUE]
      ),
      'violations' => [
        '' => 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are not allowed by the "<em class="placeholder">Limit allowed HTML tags and correct faulty HTML</em>" (<em class="placeholder">filter_html</em>) filter.',
      ],
    ];
    $data['VALID: HTML format: empty toolbar + minimal allowed HTML'] = [
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => "<p> <br>",
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['VALID: HTML format: very minimal toolbar + minimal allowed HTML'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'bold',
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => "<p> <br> <strong>",
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['INVALID: HTML format: empty toolbar + default allowed HTML tags + <p> + <br>'] = [
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => "<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li> <dl> <dt> <dd> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id>" . "<p> <br>",
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [
        'filters.filter_html' => sprintf(
          'The current CKEditor 5 build requires the following elements and attributes: <br><code>%s</code><br>The following elements are not supported: <br><code>%s</code>',
          Html::escape('<br> <p> <* dir="ltr rtl" lang>'),
          Html::escape('<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type="1 A I"> <li> <dl> <dt> <dd> <h2 id="jump-*"> <h3 id> <h4 id> <h5 id> <h6 id>'),
        ),
      ],
    ];
    $data['INVALID: HTML format: empty toolbar + default allowed HTML tags'] = [
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => "<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li> <dl> <dt> <dd> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id>",
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [
        '' => 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are not allowed by the "<em class="placeholder">Limit allowed HTML tags and correct faulty HTML</em>" (<em class="placeholder">filter_html</em>) filter.',
      ],
    ];
    $data['INVALID Source Editable tag already provided by plugin and another available in a not enabled plugin'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'heading',
            'bold',
            'italic',
            'link',
            'sourceEditing',
            'textPartLanguage',
          ],
        ],
        'plugins' => [
          'ckeditor5_heading' => [
            'enabled_headings' => [
              'heading2',
              'heading3',
              'heading4',
              'heading5',
              'heading6',
            ],
          ],
          'ckeditor5_language' => [
            'language_list' => 'un',
          ],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [
              // Tag-only; supported by enabled plugin.
              '<strong>',
              // Tag-only; supported by disabled plugin.
              '<table>',
              // Tag-only; supported by no plugin.
              '<exotic>',
              // Tag + attributes; all supported by enabled plugin.
              '<span lang>',
              // Tag + attributes; all supported by an ineligible disabled
              // plugin (has no toolbar item, has conditions).
              '<img src>',
              // Tag + attributes; all supported by disabled plugin.
              '<code class="language-*">',
              // Tag + attributes; tag already supported by enabled plugin,
              // attributes supported by disabled plugin
              '<h2 class="text-align-center">',
              // Tag + attributes; tag already supported by enabled plugin,
              // attribute not supported by no plugin.
              '<a hreflang>',
            ],
          ],
        ],
      ],
      'image_upload' => [
        'status' => TRUE,
      ],
      'filters' => [],
      'violations' => [
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.0' => 'The following tag(s) are already supported by enabled plugins and should not be added to the Source Editing "Manually editable HTML tags" field: <em class="placeholder">Bold (&lt;strong&gt;)</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.1' => 'The following tag(s) are already supported by available plugins and should not be added to the Source Editing "Manually editable HTML tags" field. Instead, enable the following plugins to support these tags: <em class="placeholder">Table (&lt;table&gt;)</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.3' => 'The following attribute(s) are already supported by enabled plugins and should not be added to the Source Editing "Manually editable HTML tags" field: <em class="placeholder">Language (&lt;span lang&gt;)</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.5' => 'The following tag(s) are already supported by available plugins and should not be added to the Source Editing "Manually editable HTML tags" field. Instead, enable the following plugins to support these tags: <em class="placeholder">Code Block (&lt;code class=&quot;language-*&quot;&gt;)</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.6' => 'The following attribute(s) are already supported by available plugins and should not be added to the Source Editing "Manually editable HTML tags" field. Instead, enable the following plugins to support these attributes: <em class="placeholder">Alignment (&lt;h2 class=&quot;text-align-center&quot;&gt;)</em>.',
      ],
    ];
    $data['INVALID some invalid Source Editable tags provided by plugin and another available in a not enabled plugin'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'heading',
            'bold',
            'italic',
            'sourceEditing',
            'textPartLanguage',
          ],
        ],
        'plugins' => [
          'ckeditor5_heading' => [
            'enabled_headings' => [
              'heading2',
              'heading3',
              'heading4',
              'heading5',
              'heading6',
            ],
          ],
          'ckeditor5_language' => [
            'language_list' => 'un',
          ],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [
              '<aside>',
              '<footer>',
              'roy',
              '<#donk>',
              '<junior>cruft',
              '',
              '   ',
            ],
          ],
        ],
      ],
      'image_upload' => [
        'status' => TRUE,
      ],
      'filters' => [],
      'violations' => [
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.2' => 'The following tag is not valid HTML: <em class="placeholder">roy</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.3' => 'The following tag is not valid HTML: <em class="placeholder">&lt;#donk&gt;</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.4' => 'The following tag is not valid HTML: <em class="placeholder">&lt;junior&gt;cruft</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.5' => 'The following tag is not valid HTML: <em class="placeholder"></em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.6' => 'The following tag is not valid HTML: <em class="placeholder">   </em>.',
      ],
    ];

    $data['INVALID: uploadImage toolbar item condition NOT met: image uploads must be enabled'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'uploadImage',
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [],
      'violations' => [
        'settings.toolbar.items.0' => 'The <em class="placeholder">Image upload</em> toolbar item requires image uploads to be enabled.',
      ],
    ];
    $data['VALID: uploadImage toolbar item condition met: image uploads must be enabled'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'uploadImage',
          ],
        ],
        'plugins' => [
          'ckeditor5_imageResize' => [
            'allow_resize' => FALSE,
          ],
        ],
      ],
      'image_upload' => [
        'status' => TRUE,
      ],
      'filters' => [],
      'violations' => [],
    ];
    $data['INVALID: drupalMedia toolbar item condition NOT met: media filter enabled'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalMedia',
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [],
      'violations' => [
        'settings.toolbar.items.0' => 'The <em class="placeholder">Drupal media</em> toolbar item requires the <em class="placeholder">Embed media</em> filter to be enabled.',
      ],
    ];
    $data['VALID: drupalMedia toolbar item condition met: media filter enabled'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalMedia',
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'media_embed',
          'provider' => 'media',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'default_view_mode' => 'default',
            'allowed_view_modes' => [],
            'allowed_media_types' => [],
          ],
        ],
      ],
      'violations' => [
        'settings.toolbar.items.0' => 'The <em class="placeholder">Drupal media</em> toolbar item requires the <em class="placeholder">Embed media</em> filter to be enabled.',
      ],
    ];
    $data['VALID: HTML format: very minimal toolbar + wildcard in source editing HTML'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'bold',
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => ['<$text-container data-llama>'],
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => '<p data-llama> <br> <strong>',
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [],
    ];
    $self_xss_source_editing = [
      // Dangerous attribute with all values allowed.
      '<p onhover>',
      '<img on*>',
      '<blockquote style>',

      // No danger.
      '<marquee>',

      // Dangerous attribute with some values allowed.
      '<a onclick="javascript:*">',
      '<code style="foo: bar;">',

      // Also works on wildcard tags.
      '<$text-container style>',
    ];
    $data['INVALID: SourceEditing plugin configuration: self-XSS detected when using filter_html'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => $self_xss_source_editing,
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => '<p onhover style> <br> <img on*> <blockquote style> <marquee> <a onclick="javascript:*"> <code style="foo: bar;">',
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.0' => 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: <em class="placeholder">&lt;p onhover&gt;</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.1' => 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: <em class="placeholder">&lt;img on*&gt;</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.2' => 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: <em class="placeholder">&lt;blockquote style&gt;</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.4' => 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: <em class="placeholder">&lt;a onclick=&quot;javascript:*&quot;&gt;</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.5' => 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: <em class="placeholder">&lt;code style=&quot;foo: bar;&quot;&gt;</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.6' => 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: <em class="placeholder">&lt;$text-container style&gt;</em>.',
      ],
    ];
    $data['VALID: SourceEditing plugin configuration: self-XSS not detected when not using filter_html'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => $self_xss_source_editing,
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [],
      'violations' => [],
    ];

    return $data;
  }

}
