<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ckeditor5\HTMLRestrictions
 * @group ckeditor5
 */
class HTMLRestrictionsTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @dataProvider providerConstruct
   */
  public function testConstructor(array $elements, ?string $expected_exception_message): void {
    if ($expected_exception_message !== NULL) {
      $this->expectException(\InvalidArgumentException::class);
      $this->expectExceptionMessage($expected_exception_message);
    }
    new HTMLRestrictions($elements);
  }

  public function providerConstruct(): \Generator {
    // Fundamental structure.
    yield 'INVALID: list instead of key-value pairs' => [
      ['<foo>', '<bar>'],
      'An array of key-value pairs must be provided, with HTML tag names as keys.',
    ];

    // Invalid HTML tag names.
    yield 'INVALID: key-value pairs now, but invalid keys due to angular brackets' => [
      ['<foo>' => '', '<bar> ' => ''],
      '"<foo>" is not a HTML tag name, it is an actual HTML tag. Omit the angular brackets.',
    ];
    yield 'INVALID: no more angular brackets, but still leading or trailing whitespace' => [
      ['foo' => '', 'bar ' => ''],
      'The "bar " HTML tag contains trailing or leading whitespace.',
    ];
    yield 'INVALID: invalid character range' => [
      ['🦙' => ''],
      '"🦙" is not a valid HTML tag name.',
    ];
    yield 'INVALID: invalid custom element name' => [
      ['foo-bar' => '', '1-foo-bar' => ''],
      '"1-foo-bar" is not a valid HTML tag name.',
    ];
    yield 'INVALID: unknown wildcard element name' => [
      ['$foo' => TRUE],
      '"$foo" is not a valid HTML tag name.',
    ];

    // Invalid HTML tag attribute name restrictions.
    yield 'INVALID: keys valid, but not yet the values' => [
      ['foo' => '', 'bar' => ''],
      'The value for the "foo" HTML tag is neither a boolean nor an array of attribute restrictions.',
    ];
    yield 'INVALID: keys valid, values can be arrays … but not empty arrays' => [
      ['foo' => [], 'bar' => []],
      'The value for the "foo" HTML tag is an empty array. This is not permitted, specify FALSE instead to indicate no attributes are allowed. Otherwise, list allowed attributes.',
    ];
    yield 'INVALID: keys valid, values invalid attribute restrictions' => [
      ['foo' => ['baz'], 'bar' => [' qux']],
      'The "foo" HTML tag has attribute restrictions, but it is not an array of key-value pairs, with HTML tag attribute names as keys.',
    ];
    yield 'INVALID: keys valid, values invalid attribute restrictions due to invalid attribute name' => [
      ['foo' => ['baz' => ''], 'bar' => [' qux' => '']],
      'The "bar" HTML tag has an attribute restriction " qux" which contains whitespace. Omit the whitespace.',
    ];
    yield 'INVALID: keys valid, values invalid attribute restrictions due to broad wildcard instead of prefix/infix/suffix wildcard attribute name' => [
      ['foo' => ['*' => TRUE]],
      'The "foo" HTML tag has an attribute restriction "*". This implies all attributes are allowed. Remove the attribute restriction instead, or use a prefix (`*-foo`), infix (`*-foo-*`) or suffix (`foo-*`) wildcard restriction instead.',
    ];

    // Invalid HTML tag attribute value restrictions.
    yield 'INVALID: keys valid, values invalid attribute restrictions due to empty strings' => [
      ['foo' => ['baz' => ''], 'bar' => ['qux' => '']],
      'The "foo" HTML tag has an attribute restriction "baz" which is neither TRUE nor an array of attribute value restrictions.',
    ];
    yield 'INVALID: keys valid, values invalid attribute restrictions due to an empty array of allowed attribute values' => [
      ['foo' => ['baz' => TRUE], 'bar' => ['qux' => []]],
      'The "bar" HTML tag has an attribute restriction "qux" which is set to the empty array. This is not permitted, specify either TRUE to allow all attribute values, or list the attribute value restrictions.',
    ];
    yield 'INVALID: keys valid, values invalid attribute restrictions due to a list of allowed attribute values' => [
      ['foo' => ['baz' => TRUE], 'bar' => ['qux' => ['a', 'b']]],
      'The "bar" HTML tag has attribute restriction "qux", but it is not an array of key-value pairs, with HTML tag attribute values as keys and TRUE as values.',
    ];

    // Valid values.
    yield 'VALID: keys valid, boolean attribute restriction values: also valid' => [
      ['foo' => TRUE, 'bar' => FALSE],
      NULL,
    ];
    yield 'VALID: keys valid, array attribute restriction values: also valid' => [
      ['foo' => ['baz' => TRUE], 'bar' => ['qux' => ['a' => TRUE, 'b' => TRUE]]],
      NULL,
    ];

    // Invalid global attribute `*` HTML tag restrictions.
    yield 'INVALID: global attribute tag allowing no attributes' => [
      ['*' => FALSE],
      'The value for the special "*" global attribute HTML tag must be an array of attribute restrictions.',
    ];
    yield 'INVALID: global attribute tag allowing any attribute' => [
      ['*' => TRUE],
      'The value for the special "*" global attribute HTML tag must be an array of attribute restrictions.',
    ];

    // Valid global attribute `*` HTML tag restrictions.
    yield 'VALID: global attribute tag with attribute allowed' => [
      ['*' => ['foo' => TRUE]],
      NULL,
    ];
    yield 'VALID: global attribute tag with attribute forbidden' => [
      ['*' => ['foo' => FALSE]],
      NULL,
    ];
    yield 'VALID: global attribute tag with attribute allowed, specific attribute values allowed' => [
      ['*' => ['foo' => ['a' => TRUE, 'b' => TRUE]]],
      NULL,
    ];
    // @todo Nothing in Drupal core uses this ability, and no custom/contrib
    //   module is known to use this. Therefore this is left for the future.
    yield 'VALID BUT NOT YET SUPPORTED: global attribute tag with attribute allowed, specific attribute values forbidden' => [
      ['*' => ['foo' => ['a' => FALSE, 'b' => FALSE]]],
      'The "*" HTML tag has attribute restriction "foo", but it is not an array of key-value pairs, with HTML tag attribute values as keys and TRUE as values.',
    ];
  }

  /**
   * @covers ::allowsNothing()
   * @covers ::getAllowedElements()
   * @dataProvider providerCounting
   */
  public function testCounting(array $elements, bool $expected_is_empty, int $expected_concrete_only_count, int $expected_concrete_plus_wildcard_count): void {
    $r = new HTMLRestrictions($elements);
    $this->assertSame($expected_is_empty, $r->allowsNothing());
    $this->assertCount($expected_concrete_only_count, $r->getAllowedElements());
    $this->assertCount($expected_concrete_only_count, $r->getAllowedElements(TRUE));
    $this->assertCount($expected_concrete_plus_wildcard_count, $r->getAllowedElements(FALSE));
  }

  public function providerCounting(): \Generator {
    yield 'empty' => [
      [],
      TRUE,
      0,
      0,
    ];

    yield 'one concrete tag' => [
      ['a' => TRUE],
      FALSE,
      1,
      1,
    ];

    yield 'one wildcard tag: considered to allow nothing because no concrete tag to resolve onto' => [
      ['$text-container' => ['class' => ['text-align-left' => TRUE]]],
      FALSE,
      0,
      1,
    ];

    yield 'two concrete tags' => [
      ['a' => TRUE, 'b' => FALSE],
      FALSE,
      2,
      2,
    ];

    yield 'one concrete tag, one wildcard tag' => [
      ['a' => TRUE, '$text-container' => ['class' => ['text-align-left' => TRUE]]],
      FALSE,
      1,
      2,
    ];

    yield 'only globally allowed attribute: considered to allow something' => [
      ['*' => ['lang' => TRUE]],
      FALSE,
      1,
      1,
    ];

    yield 'only globally forbidden attribute: considered to allow nothing' => [
      ['*' => ['style' => FALSE]],
      TRUE,
      1,
      1,
    ];
  }

  /**
   * @covers ::fromString()
   * @covers ::fromTextFormat()
   * @covers ::fromFilterPluginInstance()
   * @dataProvider providerConvenienceConstructors
   */
  public function testConvenienceConstructors($input, array $expected, ?array $expected_raw = NULL): void {
    $expected_raw = $expected_raw ?? $expected;

    // ::fromString()
    $this->assertSame($expected, HTMLRestrictions::fromString($input)->getAllowedElements());
    $this->assertSame($expected_raw, HTMLRestrictions::fromString($input)->getAllowedElements(FALSE));

    // ::fromTextFormat()
    $text_format = $this->prophesize(FilterFormatInterface::class);
    $text_format->getHTMLRestrictions()->willReturn([
      'allowed' => $expected_raw,
    ]);
    $this->assertSame($expected, HTMLRestrictions::fromTextFormat($text_format->reveal())->getAllowedElements());
    $this->assertSame($expected_raw, HTMLRestrictions::fromTextFormat($text_format->reveal())->getAllowedElements(FALSE));

    // @see \Drupal\filter\Plugin\Filter\FilterHtml::getHTMLRestrictions()
    $filter_html_additional_expectations = [
      '*' => [
        'style' => FALSE,
        'on*' => FALSE,
        'lang' => TRUE,
        'dir' => ['ltr' => TRUE, 'rtl' => TRUE],
      ],
    ];
    // ::fromFilterPluginInstance()
    $filter_plugin_instance = $this->prophesize(FilterInterface::class);
    $filter_plugin_instance->getHTMLRestrictions()->willReturn([
      'allowed' => $expected_raw + $filter_html_additional_expectations,
    ]);
    $this->assertSame($expected + $filter_html_additional_expectations, HTMLRestrictions::fromFilterPluginInstance($filter_plugin_instance->reveal())->getAllowedElements());
    $this->assertSame($expected_raw + $filter_html_additional_expectations, HTMLRestrictions::fromFilterPluginInstance($filter_plugin_instance->reveal())->getAllowedElements(FALSE));
  }

  public function providerConvenienceConstructors(): \Generator {
    // All empty cases.
    yield 'empty string' => [
      '',
      [],
    ];
    yield 'empty array' => [
      implode(' ', []),
      [],
    ];
    yield 'whitespace string' => [
      '             ',
      [],
    ];

    // Some nonsense cases.
    yield 'nonsense string' => [
      'Hello there, this looks nothing like a HTML restriction.',
      [],
    ];
    yield 'nonsense array #1' => [
      implode(' ', ['foo', 'bar']),
      [],
    ];
    yield 'nonsense array #2' => [
      implode(' ', ['foo' => TRUE, 'bar' => FALSE]),
      [],
    ];

    // Single tag cases.
    yield 'tag without attributes' => [
      '<a>',
      ['a' => FALSE],
    ];
    yield 'tag with wildcard attribute' => [
      '<a *>',
      ['a' => TRUE],
    ];
    yield 'tag with single attribute allowing any value' => [
      '<a target>',
      ['a' => ['target' => TRUE]],
    ];
    yield 'tag with single attribute allowing single specific value' => [
      '<a target="_blank">',
      ['a' => ['target' => ['_blank' => TRUE]]],
    ];
    yield 'tag with single attribute allowing multiple specific values' => [
      '<a target="_self _blank">',
      ['a' => ['target' => ['_self' => TRUE, '_blank' => TRUE]]],
    ];
    yield 'tag with single attribute allowing multiple specific values (reverse order)' => [
      '<a target="_blank _self">',
      ['a' => ['target' => ['_blank' => TRUE, '_self' => TRUE]]],
    ];
    yield 'tag with two attributes' => [
      '<a target class>',
      ['a' => ['target' => TRUE, 'class' => TRUE]],
    ];

    // Multiple tag cases.
    yield 'two tags' => [
      '<a> <p>',
      ['a' => FALSE, 'p' => FALSE],
    ];
    yield 'two tags (reverse order)' => [
      '<a> <p>',
      ['a' => FALSE, 'p' => FALSE],
    ];

    // Wildcard tag, attribute and attribute value.
    yield '$text-container' => [
      '<$text-container class="text-align-left text-align-center text-align-right text-align-justify">',
      [],
      [
        '$text-container' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$text-container + one concrete tag to resolve into' => [
      '<p> <$text-container class="text-align-left text-align-center text-align-right text-align-justify">',
      [
        'p' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
      [
        'p' => FALSE,
        '$text-container' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$text-container + two concrete tag to resolve into' => [
      '<p> <$text-container class="text-align-left text-align-center text-align-right text-align-justify"> <div>',
      [
        'p' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
        'div' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
      [
        'p' => FALSE,
        'div' => FALSE,
        '$text-container' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$text-container + one concrete tag to resolve into that already allows a subset of attributes: concrete less permissive than wildcard' => [
      '<p class="text-align-left"> <$text-container class="text-align-left text-align-center text-align-right text-align-justify">',
      [
        'p' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
      [
        'p' => [
          'class' => [
            'text-align-left' => TRUE,
          ],
        ],
        '$text-container' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$text-container + one concrete tag to resolve into that already allows all attribute values: concrete more permissive than wildcard' => [
      '<p class> <$text-container class="text-align-left text-align-center text-align-right text-align-justify">',
      [
        'p' => [
          'class' => TRUE,
        ],
      ],
      [
        'p' => [
          'class' => TRUE,
        ],
        '$text-container' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$text-container + one concrete tag to resolve into that already allows all attributes: concrete more permissive than wildcard' => [
      '<p *> <$text-container class="text-align-left text-align-center text-align-right text-align-justify">',
      [
        'p' => TRUE,
      ],
      [
        'p' => TRUE,
        '$text-container' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '<drupal-media data-*>' => [
      '<drupal-media data-*>',
      ['drupal-media' => ['data-*' => TRUE]],
    ];
    yield '<drupal-media foo-*-bar>' => [
      '<drupal-media foo-*-bar>',
      ['drupal-media' => ['foo-*-bar' => TRUE]],
    ];
    yield '<drupal-media *-foo>' => [
      '<drupal-media *-foo>',
      ['drupal-media' => ['*-foo' => TRUE]],
    ];
    yield '<h2 id="jump-*">' => [
      '<h2 id="jump-*">',
      ['h2' => ['id' => ['jump-*' => TRUE]]],
    ];
  }

  /**
   * @covers ::toCKEditor5ElementsArray()
   * @covers ::toFilterHtmlAllowedTagsString()
   * @covers ::toGeneralHtmlSupportConfig()
   * @dataProvider providerRepresentations
   */
  public function testRepresentations(HTMLRestrictions $restrictions, array $expected_elements_array, string $expected_allowed_html_string, array $expected_ghs_config): void {
    $this->assertSame($expected_elements_array, $restrictions->toCKEditor5ElementsArray());
    $this->assertSame($expected_allowed_html_string, $restrictions->toFilterHtmlAllowedTagsString());
    $this->assertSame($expected_ghs_config, $restrictions->toGeneralHtmlSupportConfig());
  }

  public function providerRepresentations(): \Generator {
    yield 'empty set' => [
      HTMLRestrictions::emptySet(),
      [],
      '',
      [],
    ];

    yield 'only tags' => [
      new HTMLRestrictions(['a' => FALSE, 'p' => FALSE, 'br' => FALSE]),
      ['<a>', '<p>', '<br>'],
      '<a> <p> <br>',
      [
        ['name' => 'a'],
        ['name' => 'p'],
        ['name' => 'br'],
      ],
    ];

    yield 'single tag with multiple attributes allowing all values' => [
      new HTMLRestrictions(['script' => ['src' => TRUE, 'defer' => TRUE]]),
      ['<script src defer>'],
      '<script src defer>',
      [
        [
          'name' => 'script',
          'attributes' => [
            ['key' => 'src', 'value' => TRUE],
            ['key' => 'defer', 'value' => TRUE],
          ],
        ],
      ],
    ];

    yield '$text-container wildcard' => [
      new HTMLRestrictions(['$text-container' => ['class' => TRUE, 'data-llama' => TRUE], 'div' => FALSE, 'span' => FALSE, 'p' => ['id' => TRUE]]),
      ['<$text-container class data-llama>', '<div>', '<span>', '<p id>'],
      '<div class data-llama> <span> <p id class data-llama>',
      [
        [
          'name' => 'div',
          'classes' => TRUE,
          'attributes' => [
            [
              'key' => 'data-llama',
              'value' => TRUE,
            ],
          ],
        ],
        ['name' => 'span'],
        [
          'name' => 'p',
          'attributes' => [
            [
              'key' => 'id',
              'value' => TRUE,
            ],
            [
              'key' => 'data-llama',
              'value' => TRUE,
            ],
          ],
          'classes' => TRUE,
        ],
      ],
    ];

    yield 'realistic' => [
      new HTMLRestrictions(['a' => ['href' => TRUE, 'hreflang' => ['en' => TRUE, 'fr' => TRUE]], 'p' => ['data-*' => TRUE, 'class' => ['block' => TRUE]], 'br' => FALSE]),
      ['<a href hreflang="en fr">', '<p data-* class="block">', '<br>'],
      '<a href hreflang="en fr"> <p data-* class="block"> <br>',
      [
        [
          'name' => 'a',
          'attributes' => [
            ['key' => 'href', 'value' => TRUE],
            [
              'key' => 'hreflang',
              'value' => [
                'regexp' => [
                  'pattern' => '/^(en|fr)$/',
                ],
              ],
            ],
          ],
        ],
        [
          'name' => 'p',
          'attributes' => [
            [
              'key' => [
                'regexp' => [
                  'pattern' => '/^data-.*$/',
                ],
              ],
              'value' => TRUE,
            ],
          ],
          'classes' => [
            'regexp' => [
              'pattern' => '/^(block)$/',
            ],
          ],
        ],
        ['name' => 'br'],
      ],
    ];

    // Wildcard tag, attribute and attribute value.
    yield '$text-container' => [
      new HTMLRestrictions(['p' => FALSE, '$text-container' => ['data-*' => TRUE]]),
      ['<p>', '<$text-container data-*>'],
      '<p data-*>',
      [
        [
          'name' => 'p',
          'attributes' => [
            [
              'key' => [
                'regexp' => [
                  'pattern' => '/^data-.*$/',
                ],
              ],
              'value' => TRUE,
            ],
          ],
        ],
      ],
    ];
    yield '<drupal-media data-*>' => [
      new HTMLRestrictions(['drupal-media' => ['data-*' => TRUE]]),
      ['<drupal-media data-*>'],
      '<drupal-media data-*>',
      [
        [
          'name' => 'drupal-media',
          'attributes' => [
            [
              'key' => [
                'regexp' => [
                  'pattern' => '/^data-.*$/',
                ],
              ],
              'value' => TRUE,
            ],
          ],
        ],
      ],
    ];
    yield '<drupal-media foo-*-bar>' => [
      new HTMLRestrictions(['drupal-media' => ['foo-*-bar' => TRUE]]),
      ['<drupal-media foo-*-bar>'],
      '<drupal-media foo-*-bar>',
      [
        [
          'name' => 'drupal-media',
          'attributes' => [
            [
              'key' => [
                'regexp' => [
                  'pattern' => '/^foo-.*-bar$/',
                ],
              ],
              'value' => TRUE,
            ],
          ],
        ],
      ],
    ];
    yield '<drupal-media *-bar>' => [
      new HTMLRestrictions(['drupal-media' => ['*-bar' => TRUE]]),
      ['<drupal-media *-bar>'],
      '<drupal-media *-bar>',
      [
        [
          'name' => 'drupal-media',
          'attributes' => [
            [
              'key' => [
                'regexp' => [
                  'pattern' => '/^.*-bar$/',
                ],
              ],
              'value' => TRUE,
            ],
          ],
        ],
      ],
    ];
    yield '<h2 id="jump-*">' => [
      new HTMLRestrictions(['h2' => ['id' => ['jump-*' => TRUE]]]),
      ['<h2 id="jump-*">'],
      '<h2 id="jump-*">',
      [
        [
          'name' => 'h2',
          'attributes' => [
            [
              'key' => 'id',
              'value' => [
                'regexp' => [
                  'pattern' => '/^(jump-.*)$/',
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::diff()
   * @covers ::intersect()
   * @covers ::merge()
   * @dataProvider providerOperands
   */
  public function testOperations(HTMLRestrictions $a, HTMLRestrictions $b, $expected_diff, $expected_intersection, $expected_union): void {
    // This looks more complicated than it is: it applies the same processing to
    // all three of the expected operation results.
    foreach (['diff', 'intersection', 'union'] as $op) {
      $parameter = "expected_$op";
      // Ensure that the operation expectation is 'a' or 'b' whenever possible.
      if ($a == $$parameter) {
        throw new \LogicException("List 'a' as the expected $op rather than specifying it in full, to keep the tests legible.");
      }
      else {
        if ($b == $$parameter) {
          throw new \LogicException("List 'b' as the expected $op rather than specifying it in full, to keep the tests legible.");
        }
      }
      // Map any expected 'a' or 'b' string value to the corresponding operand.
      if ($$parameter === 'a') {
        $$parameter = $a;
      }
      elseif ($$parameter === 'b') {
        $$parameter = $b;
      }
      assert($$parameter instanceof HTMLRestrictions);
    }
    $this->assertEquals($expected_diff, $a->diff($b));
    $this->assertEquals($expected_intersection, $a->intersect($b));
    $this->assertEquals($expected_union, $a->merge($b));
  }

  public function providerOperands(): \Generator {
    // Empty set operand cases.
    yield 'any set + empty set' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => HTMLRestrictions::emptySet(),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'empty set + any set' => [
      'a' => HTMLRestrictions::emptySet(),
      'b' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'diff' => 'a',
      'intersection' => 'a',
      'union' => 'b',
    ];

    // Basic cases: tags.
    yield 'union of two very restricted tags' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'a',
    ];
    yield 'union of two very unrestricted tags' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'a',
    ];
    yield 'union of one very unrestricted tag with one very restricted tag' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'union of one very unrestricted tag with one very restricted tag — vice versa' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];

    // Basic cases: attributes..
    yield 'set + set with empty intersection' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['b' => ['href' => TRUE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['a' => ['href' => TRUE], 'b' => ['href' => TRUE]]),
    ];
    yield 'set + identical set' => [
      'a' => new HTMLRestrictions(['b' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['b' => ['href' => TRUE]]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'b',
      'union' => 'b',
    ];
    yield 'set + superset' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['b' => ['href' => TRUE], 'a' => ['href' => TRUE]]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];

    // Tag restrictions.
    yield 'tag restrictions are different: <a> vs <b c>' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['b' => ['c' => TRUE]]),
      'diff' => 'a',
      'intersect' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['a' => FALSE, 'b' => ['c' => TRUE]]),
    ];
    yield 'tag restrictions are different: <a> vs <b c> — vice versa' => [
      'a' => new HTMLRestrictions(['b' => ['c' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'diff' => 'a',
      'intersect' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['a' => FALSE, 'b' => ['c' => TRUE]]),
    ];
    yield 'tag restrictions are different: <a *> vs <b c>' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['b' => ['c' => TRUE]]),
      'diff' => 'a',
      'intersect' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['a' => TRUE, 'b' => ['c' => TRUE]]),
    ];
    yield 'tag restrictions are different: <a *> vs <b c> — vice versa' => [
      'a' => new HTMLRestrictions(['b' => ['c' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'diff' => 'a',
      'intersect' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['a' => TRUE, 'b' => ['c' => TRUE]]),
    ];

    // Attribute restrictions.
    yield 'attribute restrictions are less permissive: <a *> vs <a>' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'attribute restrictions are more permissive: <a> vs <a *>' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];

    yield 'attribute restrictions are more permissive: <a href> vs <a *>' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];
    yield 'attribute restrictions are more permissive: <a> vs <a href>' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];
    yield 'attribute restrictions are more restrictive: <a href> vs <a>' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'attribute restrictions are more restrictive: <a *> vs <a href>' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'attribute restrictions are different: <a href> vs <a hreflang>' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => TRUE]]),
      'diff' => 'a',
      'intersection' => new HTMLRestrictions(['a' => FALSE]),
      'union' => new HTMLRestrictions(['a' => ['href' => TRUE, 'hreflang' => TRUE]]),
    ];
    yield 'attribute restrictions are different: <a href> vs <a hreflang> — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'diff' => 'a',
      'intersection' => new HTMLRestrictions(['a' => FALSE]),
      'union' => new HTMLRestrictions(['a' => ['href' => TRUE, 'hreflang' => TRUE]]),
    ];

    // Attribute value restriction.
    yield 'attribute restrictions are different: <a hreflang="en"> vs <a hreflang="fr">' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['fr' => TRUE]]]),
      'diff' => 'a',
      'intersection' => new HTMLRestrictions(['a' => FALSE]),
      'union' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE, 'fr' => TRUE]]]),
    ];
    yield 'attribute restrictions are different: <a hreflang="en"> vs <a hreflang="fr"> — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['fr' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'diff' => 'a',
      'intersection' => new HTMLRestrictions(['a' => FALSE]),
      'union' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE, 'fr' => TRUE]]]),
    ];
    yield 'attribute restrictions are different: <a hreflang=*> vs <a hreflang="en">' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'attribute restrictions are different: <a hreflang=*> vs <a hreflang="en"> — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => TRUE]]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];

    // Complex cases.
    yield 'attribute restrictions are different: <a hreflang="en"> vs <strong>' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['strong' => TRUE]),
      'diff' => 'a',
      'intersect' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]], 'strong' => TRUE]),
    ];
    yield 'attribute restrictions are different: <a hreflang="en"> vs <strong> — vice versa' => [
      'a' => new HTMLRestrictions(['strong' => TRUE]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'diff' => 'a',
      'intersect' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]], 'strong' => TRUE]),
    ];
    yield 'very restricted tag + slightly restricted tag' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];
    yield 'very restricted tag + slightly restricted tag — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'very unrestricted tag + slightly restricted tag' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'very unrestricted tag + slightly restricted tag — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];

    // Wildcard tag + matching tag cases.
    yield 'wildcard + matching tag: attribute intersection — without possible resolving' => [
      'a' => new HTMLRestrictions(['p' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['p' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + matching tag: attribute intersection — without possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['p' => ['class' => TRUE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['p' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + matching tag: attribute intersection — WITH possible resolving' => [
      'a' => new HTMLRestrictions(['p' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'p' => FALSE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => new HTMLRestrictions(['p' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + matching tag: attribute intersection — WITH possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'p' => FALSE]),
      'b' => new HTMLRestrictions(['p' => ['class' => TRUE]]),
      'diff' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'intersection' => 'b',
      'union' => new HTMLRestrictions(['p' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + matching tag: attribute value intersection — without possible resolving' => [
      'a' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE]]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]], '$text-container' => ['class' => ['text-align-center' => TRUE]]]),
    ];
    yield 'wildcard + matching tag: attribute value intersection — without possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE]]]),
      'b' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]], '$text-container' => ['class' => ['text-align-center' => TRUE]]]),
    ];
    yield 'wildcard + matching tag: attribute value intersection — WITH possible resolving' => [
      'a' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE]], 'p' => FALSE]),
      'diff' => new HTMLRestrictions(['p' => ['class' => ['text-align-justify' => TRUE]]]),
      'intersection' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE]]]),
      'union' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]], '$text-container' => ['class' => ['text-align-center' => TRUE]]]),
    ];
    yield 'wildcard + matching tag: attribute value intersection — WITH possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE]], 'p' => FALSE]),
      'b' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'diff' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE]]]),
      'intersection' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE]]]),
      'union' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]], '$text-container' => ['class' => ['text-align-center' => TRUE]]]),
    ];
    yield 'wildcard + matching tag: on both sides' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE, 'foo' => TRUE], 'p' => FALSE]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'p' => FALSE]),
      'diff' => new HTMLRestrictions(['$text-container' => ['foo' => TRUE], 'p' => ['foo' => TRUE]]),
      'intersection' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'p' => ['class' => TRUE]]),
      'union' => 'a',
    ];
    yield 'wildcard + matching tag: on both sides — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'p' => FALSE]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE, 'foo' => TRUE], 'p' => FALSE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'p' => ['class' => TRUE]]),
      'union' => 'b',
    ];

    // Wildcard tag + non-matching tag cases.
    yield 'wildcard + non-matching tag: attribute diff — without possible resolving' => [
      'a' => new HTMLRestrictions(['span' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['span' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + non-matching tag: attribute diff — without possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['span' => ['class' => TRUE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['span' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + non-matching tag: attribute diff — WITH possible resolving' => [
      'a' => new HTMLRestrictions(['span' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'span' => FALSE]),
      'diff' => 'a',
      'intersection' => new HTMLRestrictions(['span' => FALSE]),
      'union' => new HTMLRestrictions(['span' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + non-matching tag: attribute diff — WITH possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'span' => FALSE]),
      'b' => new HTMLRestrictions(['span' => ['class' => TRUE]]),
      'diff' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'intersection' => new HTMLRestrictions(['span' => FALSE]),
      'union' => new HTMLRestrictions(['span' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + non-matching tag: attribute value diff — without possible resolving' => [
      'a' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => ['vertical-align-top' => TRUE]]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]], '$text-container' => ['class' => ['vertical-align-top' => TRUE]]]),
    ];
    yield 'wildcard + non-matching tag: attribute value diff — without possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => ['vertical-align-top' => TRUE]]]),
      'b' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]], '$text-container' => ['class' => ['vertical-align-top' => TRUE]]]),
    ];
    yield 'wildcard + non-matching tag: attribute value diff — WITH possible resolving' => [
      'a' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => ['vertical-align-top' => TRUE]], 'span' => FALSE]),
      'diff' => 'a',
      'intersection' => new HTMLRestrictions(['span' => FALSE]),
      'union' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]], '$text-container' => ['class' => ['vertical-align-top' => TRUE]]]),
    ];
    yield 'wildcard + non-matching tag: attribute value diff — WITH possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => ['vertical-align-top' => TRUE]], 'span' => FALSE]),
      'b' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]]]),
      'diff' => new HTMLRestrictions(['$text-container' => ['class' => ['vertical-align-top' => TRUE]]]),
      'intersection' => new HTMLRestrictions(['span' => FALSE]),
      'union' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]], '$text-container' => ['class' => ['vertical-align-top' => TRUE]]]),
    ];

    // Wildcard tag + wildcard tag cases.
    yield 'wildcard + wildcard tag: attributes' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE, 'foo' => TRUE]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'diff' => new HTMLRestrictions(['$text-container' => ['foo' => TRUE]]),
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'wildcard + wildcard tag: attributes — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE, 'foo' => TRUE]]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];
    yield 'wildcard + wildcard tag: attribute values' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE]]]),
      'diff' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-justify' => TRUE]]]),
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'wildcard + wildcard tag: attribute values — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE]]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];

    // Concrete attributes + wildcard attribute cases for all 3 possible
    // wildcard locations. Parametrized to prevent excessive repetition and
    // subtle differences.
    $wildcard_locations = [
      'prefix' => 'data-*',
      'infix' => '*-entity-*',
      'suffix' => '*-type',
    ];
    foreach ($wildcard_locations as $wildcard_location => $wildcard_attr_name) {
      yield "concrete attrs + wildcard $wildcard_location attr that covers a superset" => [
        'a' => new HTMLRestrictions(['img' => ['data-entity-bundle-type' => TRUE, 'data-entity-type' => TRUE]]),
        'b' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE]]),
        'diff' => HTMLRestrictions::emptySet(),
        'intersection' => 'a',
        'union' => 'b',
      ];
      yield "concrete attrs + wildcard $wildcard_location attr that covers a superset — vice versa" => [
        'a' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE]]),
        'b' => new HTMLRestrictions(['img' => ['data-entity-bundle-type' => TRUE, 'data-entity-type' => TRUE]]),
        'diff' => 'a',
        'intersection' => 'b',
        'union' => 'a',
      ];
      yield "concrete attrs + wildcard $wildcard_location attr that covers a subset" => [
        'a' => new HTMLRestrictions(['img' => ['data-entity-bundle-type' => TRUE, 'data-entity-type' => TRUE, 'class' => TRUE]]),
        'b' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE]]),
        'diff' => new HTMLRestrictions(['img' => ['class' => TRUE]]),
        'intersection' => new HTMLRestrictions(['img' => ['data-entity-bundle-type' => TRUE, 'data-entity-type' => TRUE]]),
        'union' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE, 'class' => TRUE]]),
      ];
      yield "concrete attrs + wildcard $wildcard_location attr that covers a subset — vice versa" => [
        'a' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE]]),
        'b' => new HTMLRestrictions(['img' => ['data-entity-bundle-type' => TRUE, 'data-entity-type' => TRUE, 'class' => TRUE]]),
        'diff' => 'a',
        'intersection' => new HTMLRestrictions(['img' => ['data-entity-bundle-type' => TRUE, 'data-entity-type' => TRUE]]),
        'union' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE, 'class' => TRUE]]),
      ];
      yield "wildcard $wildcard_location attr + wildcard $wildcard_location attr" => [
        'a' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE, 'class' => TRUE]]),
        'b' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE]]),
        'diff' => new HTMLRestrictions(['img' => ['class' => TRUE]]),
        'intersection' => 'b',
        'union' => 'a',
      ];
      yield "wildcard $wildcard_location attr + wildcard $wildcard_location attr — vice versa" => [
        'a' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE]]),
        'b' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE, 'class' => TRUE]]),
        'diff' => HTMLRestrictions::emptySet(),
        'intersection' => 'a',
        'union' => 'b',
      ];
    }

    // Global attribute `*` HTML tag + global attribute `*` HTML tag cases.
    yield 'global attribute tag + global attribute tag: no overlap in attributes' => [
      'a' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'b' => new HTMLRestrictions(['*' => ['baz' => FALSE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE, 'baz' => FALSE]]),
    ];
    yield 'global attribute tag + global attribute tag: no overlap in attributes — vice versa' => [
      'a' => new HTMLRestrictions(['*' => ['baz' => FALSE]]),
      'b' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE, 'baz' => FALSE]]),
    ];
    yield 'global attribute tag + global attribute tag: overlap in attributes, same attribute value restrictions' => [
      'a' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      'b' => new HTMLRestrictions(['*' => ['bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      'diff' => new HTMLRestrictions(['*' => ['foo' => TRUE]]),
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'global attribute tag + global attribute tag: overlap in attributes, same attribute value restrictions — vice versa' => [
      'a' => new HTMLRestrictions(['*' => ['bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      'b' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];
    yield 'global attribute tag + global attribute tag: overlap in attributes, different attribute value restrictions' => [
      'a' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      'b' => new HTMLRestrictions(['*' => ['bar' => TRUE, 'dir' => TRUE, 'foo' => FALSE]]),
      'diff' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'intersection' => new HTMLRestrictions(['*' => ['bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE], 'foo' => FALSE]]),
      'union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => TRUE, 'dir' => TRUE]]),
    ];
    yield 'global attribute tag + global attribute tag: overlap in attributes, different attribute value restrictions — vice versa' => [
      'a' => new HTMLRestrictions(['*' => ['bar' => TRUE, 'dir' => TRUE, 'foo' => FALSE]]),
      'b' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      'diff' => 'a',
      'intersection' => new HTMLRestrictions(['*' => ['bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE], 'foo' => FALSE]]),
      'union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => TRUE, 'dir' => TRUE]]),
    ];

    // Global attribute `*` HTML tag + concrete tag.
    yield 'global attribute tag + concrete tag' => [
      'a' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'b' => new HTMLRestrictions(['p' => FALSE]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE], 'p' => FALSE]),
    ];
    yield 'global attribute tag + concrete tag — vice versa' => [
      'a' => new HTMLRestrictions(['p' => FALSE]),
      'b' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE], 'p' => FALSE]),
    ];
    yield 'global attribute tag + concrete tag with allowed attribute' => [
      'a' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'b' => new HTMLRestrictions(['p' => ['baz' => TRUE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE], 'p' => ['baz' => TRUE]]),
    ];
    yield 'global attribute tag + concrete tag with allowed attribute — vice versa' => [
      'a' => new HTMLRestrictions(['p' => ['baz' => TRUE]]),
      'b' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE], 'p' => ['baz' => TRUE]]),
    ];

    // Global attribute `*` HTML tag + wildcard tag.
    yield 'global attribute tag + wildcard tag' => [
      'a' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'global attribute tag + wildcard tag — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE], '$text-container' => ['class' => TRUE]]),
    ];
  }

  /**
   * @covers ::getWildcardSubset
   * @covers ::getConcreteSubset
   * @dataProvider providerSubsets
   */
  public function testSubsets(HTMLRestrictions $input, HTMLRestrictions $expected_wildcard_subset, HTMLRestrictions $expected_concrete_subset): void {
    $this->assertEquals($expected_wildcard_subset, $input->getWildcardSubset());
    $this->assertEquals($expected_concrete_subset, $input->getConcreteSubset());
  }

  public function providerSubsets(): \Generator {
    yield 'empty set' => [
      new HTMLRestrictions([]),
      new HTMLRestrictions([]),
      new HTMLRestrictions([]),
    ];

    yield 'without wildcards' => [
      new HTMLRestrictions(['div' => FALSE]),
      new HTMLRestrictions([]),
      new HTMLRestrictions(['div' => FALSE]),
    ];

    yield 'with wildcards' => [
      new HTMLRestrictions(['div' => FALSE, '$text-container' => ['data-llama' => TRUE], '*' => ['on*' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      new HTMLRestrictions(['$text-container' => ['data-llama' => TRUE]]),
      new HTMLRestrictions(['div' => FALSE, '*' => ['on*' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
    ];

    yield 'wildcards and global attribute tag' => [
      new HTMLRestrictions(['$text-container' => ['data-llama' => TRUE], '*' => ['on*' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      new HTMLRestrictions(['$text-container' => ['data-llama' => TRUE]]),
      new HTMLRestrictions(['*' => ['on*' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
    ];

    yield 'only wildcards' => [
      new HTMLRestrictions(['$text-container' => ['data-llama' => TRUE]]),
      new HTMLRestrictions(['$text-container' => ['data-llama' => TRUE]]),
      new HTMLRestrictions([]),
    ];
  }

}
