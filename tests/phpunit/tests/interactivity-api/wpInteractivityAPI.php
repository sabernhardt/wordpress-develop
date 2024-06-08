<?php
/**
 * Unit tests covering WP_Interactivity_API functionality.
 *
 * @package WordPress
 * @subpackage Interactivity API
 *
 * @since 6.5.0
 *
 * @group interactivity-api
 *
 * @coversDefaultClass WP_Interactivity_API
 */
class Tests_Interactivity_API_WpInteractivityAPI extends WP_UnitTestCase {
	/**
	 * Instance of WP_Interactivity_API.
	 *
	 * @var WP_Interactivity_API
	 */
	protected $interactivity;

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		$this->interactivity = new WP_Interactivity_API();
	}

	public function charset_iso_8859_1() {
		return 'iso-8859-1';
	}

	/**
	 * Modifies the internal namespace stack as if the WP_Interactivity_API
	 * instance had found `data-wp-interactive` directives during
	 * `process_directives` execution.
	 *
	 * @param array<string> $stack Values for the internal namespace stack.
	 */
	private function set_internal_namespace_stack( ...$stack ) {
		$interactivity   = new ReflectionClass( $this->interactivity );
		$namespace_stack = $interactivity->getProperty( 'namespace_stack' );
		$namespace_stack->setAccessible( true );
		$namespace_stack->setValue( $this->interactivity, $stack );
	}

	/**
	 * Modifies the internal context stack as if the WP_Interactivity_API
	 * instance had found `data-wp-context` directives during
	 * `process_directives` execution.
	 *
	 * @param array<array<mixed>> $stack Values for the internal context stack.
	 */
	private function set_internal_context_stack( ...$stack ) {
		$interactivity = new ReflectionClass( $this->interactivity );
		$context_stack = $interactivity->getProperty( 'context_stack' );
		$context_stack->setAccessible( true );
		$context_stack->setValue( $this->interactivity, $stack );
	}

	/**
	 * Tests that the state and config methods return an empty array at the
	 * beginning.
	 *
	 * @ticket 60356
	 *
	 * @covers ::state
	 * @covers ::config
	 */
	public function test_state_and_config_should_be_empty() {
		$this->assertEquals( array(), $this->interactivity->state( 'myPlugin' ) );
		$this->assertEquals( array(), $this->interactivity->config( 'myPlugin' ) );
	}

	/**
	 * Tests that the state and config methods can change the state and
	 * configuration.
	 *
	 * @ticket 60356
	 *
	 * @covers ::state
	 * @covers ::config
	 */
	public function test_state_and_config_can_be_changed() {
		$state  = array(
			'a'      => 1,
			'b'      => 2,
			'nested' => array( 'c' => 3 ),
		);
		$result = $this->interactivity->state( 'myPlugin', $state );
		$this->assertEquals( $state, $result );
		$result = $this->interactivity->config( 'myPlugin', $state );
		$this->assertEquals( $state, $result );
	}

	/**
	 * Tests that different initial states and configurations can be merged.
	 *
	 * @ticket 60356
	 *
	 * @covers ::state
	 * @covers ::config
	 */
	public function test_state_and_config_can_be_merged() {
		$this->interactivity->state( 'myPlugin', array( 'a' => 1 ) );
		$this->interactivity->state( 'myPlugin', array( 'b' => 2 ) );
		$this->interactivity->state( 'otherPlugin', array( 'c' => 3 ) );
		$this->assertEquals(
			array(
				'a' => 1,
				'b' => 2,
			),
			$this->interactivity->state( 'myPlugin' )
		);
		$this->assertEquals(
			array( 'c' => 3 ),
			$this->interactivity->state( 'otherPlugin' )
		);

		$this->interactivity->config( 'myPlugin', array( 'a' => 1 ) );
		$this->interactivity->config( 'myPlugin', array( 'b' => 2 ) );
		$this->interactivity->config( 'otherPlugin', array( 'c' => 3 ) );
		$this->assertEquals(
			array(
				'a' => 1,
				'b' => 2,
			),
			$this->interactivity->config( 'myPlugin' )
		);
		$this->assertEquals(
			array( 'c' => 3 ),
			$this->interactivity->config( 'otherPlugin' )
		);  }

	/**
	 * Tests that existing keys in the initial state and configuration can be
	 * overwritten.
	 *
	 * @ticket 60356
	 *
	 * @covers ::state
	 * @covers ::config
	 */
	public function test_state_and_config_existing_props_can_be_overwritten() {
		$this->interactivity->state( 'myPlugin', array( 'a' => 1 ) );
		$this->interactivity->state( 'myPlugin', array( 'a' => 2 ) );
		$this->assertEquals(
			array( 'a' => 2 ),
			$this->interactivity->state( 'myPlugin' )
		);

		$this->interactivity->config( 'myPlugin', array( 'a' => 1 ) );
		$this->interactivity->config( 'myPlugin', array( 'a' => 2 ) );
		$this->assertEquals(
			array( 'a' => 2 ),
			$this->interactivity->config( 'myPlugin' )
		);
	}

	/**
	 * Tests that existing indexed arrays in the initial state and configuration
	 * are replaced, not merged.
	 *
	 * @ticket 60356
	 *
	 * @covers ::state
	 * @covers ::config
	 */
	public function test_state_and_config_existing_indexed_arrays_are_replaced() {
		$this->interactivity->state( 'myPlugin', array( 'a' => array( 1, 2 ) ) );
		$this->interactivity->state( 'myPlugin', array( 'a' => array( 3, 4 ) ) );
		$this->assertEquals(
			array( 'a' => array( 3, 4 ) ),
			$this->interactivity->state( 'myPlugin' )
		);

		$this->interactivity->config( 'myPlugin', array( 'a' => array( 1, 2 ) ) );
		$this->interactivity->config( 'myPlugin', array( 'a' => array( 3, 4 ) ) );
		$this->assertEquals(
			array( 'a' => array( 3, 4 ) ),
			$this->interactivity->config( 'myPlugin' )
		);
	}

	/**
	 * Invokes the private `print_client_interactivity` method of
	 * WP_Interactivity_API class.
	 *
	 * @return array|null The content of the JSON object printed on the client-side or null if nothings was printed.
	 */
	private function print_client_interactivity_data() {
		$interactivity_data_markup = get_echo( array( $this->interactivity, 'print_client_interactivity_data' ) );
		preg_match( '/<script type="application\/json" id="wp-interactivity-data">.*?(\{.*\}).*?<\/script>/s', $interactivity_data_markup, $interactivity_data_string );
		return isset( $interactivity_data_string[1] ) ? json_decode( $interactivity_data_string[1], true ) : null;
	}

	/**
	 * Tests that the initial state and config are correctly printed on the
	 * client-side.
	 *
	 * @ticket 60356
	 *
	 * @covers ::state
	 * @covers ::config
	 * @covers ::print_client_interactivity_data
	 */
	public function test_state_and_config_is_correctly_printed() {
		$this->interactivity->state( 'myPlugin', array( 'a' => 1 ) );
		$this->interactivity->state( 'otherPlugin', array( 'b' => 2 ) );
		$this->interactivity->config( 'myPlugin', array( 'a' => 1 ) );
		$this->interactivity->config( 'otherPlugin', array( 'b' => 2 ) );

		$result = $this->print_client_interactivity_data();

		$data = array(
			'myPlugin'    => array( 'a' => 1 ),
			'otherPlugin' => array( 'b' => 2 ),
		);

		$this->assertEquals(
			array(
				'state'  => $data,
				'config' => $data,
			),
			$result
		);
	}

	/**
	 * Tests that the wp-interactivity-data script is not printed if both state
	 * and config are empty.
	 *
	 * @ticket 60356
	 *
	 * @covers ::print_client_interactivity_data
	 */
	public function test_state_and_config_dont_print_when_empty() {
		$result = $this->print_client_interactivity_data();
		$this->assertNull( $result );
	}

	/**
	 * Tests that the config is not printed if it's empty.
	 *
	 * @ticket 60356
	 *
	 * @covers ::state
	 * @covers ::print_client_interactivity_data
	 */
	public function test_config_not_printed_when_empty() {
		$this->interactivity->state( 'myPlugin', array( 'a' => 1 ) );
		$result = $this->print_client_interactivity_data();
		$this->assertEquals( array( 'state' => array( 'myPlugin' => array( 'a' => 1 ) ) ), $result );
	}

	/**
	 * Tests that the state is not printed if it's empty.
	 *
	 * @ticket 60356
	 *
	 * @covers ::config
	 * @covers ::print_client_interactivity_data
	 */
	public function test_state_not_printed_when_empty() {
		$this->interactivity->config( 'myPlugin', array( 'a' => 1 ) );
		$result = $this->print_client_interactivity_data();
		$this->assertEquals( array( 'config' => array( 'myPlugin' => array( 'a' => 1 ) ) ), $result );
	}

	/**
	 * Tests that empty state objects are pruned from printed data.
	 *
	 * @ticket 60761
	 *
	 * @covers ::print_client_interactivity_data
	 */
	public function test_state_not_printed_when_empty_array() {
		$this->interactivity->state( 'pluginWithEmptyState_prune', array() );
		$this->interactivity->state( 'pluginWithState_include', array( 'value' => 'excellent' ) );
		$printed_script = get_echo( array( $this->interactivity, 'print_client_interactivity_data' ) );
		$expected       = <<<'SCRIPT_TAG'
<script type="application/json" id="wp-interactivity-data">
{"state":{"pluginWithState_include":{"value":"excellent"}}}
</script>

SCRIPT_TAG;

		$this->assertSame( $expected, $printed_script );
	}

	/**
	 * Tests that data consisting of only empty state objects is not printed.
	 *
	 * @ticket 60761
	 *
	 * @covers ::print_client_interactivity_data
	 */
	public function test_state_not_printed_when_only_empty_arrays() {
		$this->interactivity->state( 'pluginWithEmptyState_prune', array() );
		$printed_script = get_echo( array( $this->interactivity, 'print_client_interactivity_data' ) );
		$this->assertSame( '', $printed_script );
	}

	/**
	 * Tests that nested empty state objects are printed correctly.
	 *
	 * @ticket 60761
	 *
	 * @covers ::print_client_interactivity_data
	 */
	public function test_state_printed_correctly_with_nested_empty_array() {
		$this->interactivity->state( 'myPlugin', array( 'emptyArray' => array() ) );
		$printed_script = get_echo( array( $this->interactivity, 'print_client_interactivity_data' ) );
		$expected       = <<<'SCRIPT_TAG'
<script type="application/json" id="wp-interactivity-data">
{"state":{"myPlugin":{"emptyArray":[]}}}
</script>

SCRIPT_TAG;

		$this->assertSame( $expected, $printed_script );
	}

	/**
	 * Tests that empty config objects are pruned from printed data.
	 *
	 * @ticket 60761
	 *
	 * @covers ::print_client_interactivity_data
	 */
	public function test_config_not_printed_when_empty_array() {
		$this->interactivity->config( 'pluginWithEmptyConfig_prune', array() );
		$this->interactivity->config( 'pluginWithConfig_include', array( 'value' => 'excellent' ) );
		$printed_script = get_echo( array( $this->interactivity, 'print_client_interactivity_data' ) );
		$expected       = <<<'SCRIPT_TAG'
<script type="application/json" id="wp-interactivity-data">
{"config":{"pluginWithConfig_include":{"value":"excellent"}}}
</script>

SCRIPT_TAG;

		$this->assertSame( $expected, $printed_script );
	}

	/**
	 * Tests that data consisting of only empty config objects is not printed.
	 *
	 * @ticket 60761
	 *
	 * @covers ::print_client_interactivity_data
	 */
	public function test_config_not_printed_when_only_empty_arrays() {
		$this->interactivity->config( 'pluginWithEmptyConfig_prune', array() );
		$printed_script = get_echo( array( $this->interactivity, 'print_client_interactivity_data' ) );
		$this->assertSame( '', $printed_script );
	}

	/**
	 * Tests that nested empty config objects are printed correctly.
	 *
	 * @ticket 60761
	 *
	 * @covers ::print_client_interactivity_data
	 */
	public function test_config_printed_correctly_with_nested_empty_array() {
		$this->interactivity->config( 'myPlugin', array( 'emptyArray' => array() ) );
		$printed_script = get_echo( array( $this->interactivity, 'print_client_interactivity_data' ) );
		$expected       = <<<'SCRIPT_TAG'
<script type="application/json" id="wp-interactivity-data">
{"config":{"myPlugin":{"emptyArray":[]}}}
</script>

SCRIPT_TAG;

		$this->assertSame( $expected, $printed_script );
	}

	/**
	 * Tests that special characters in the initial state and configuration are
	 * properly escaped.
	 *
	 * @ticket 60356
	 * @ticket 61170
	 *
	 * @covers ::state
	 * @covers ::config
	 * @covers ::print_client_interactivity_data
	 */
	public function test_state_and_config_escape_special_characters() {
		$this->interactivity->state(
			'myPlugin',
			array(
				'ampersand'                              => '&',
				'less-than sign'                         => '<',
				'greater-than sign'                      => '>',
				'solidus'                                => '/',
				'line separator'                         => "\u{2028}",
				'paragraph separator'                    => "\u{2029}",
				'flag of england'                        => "\u{1F3F4}\u{E0067}\u{E0062}\u{E0065}\u{E006E}\u{E0067}\u{E007F}",
				'malicious script closer'                => '</script>',
				'entity-encoded malicious script closer' => '&lt;/script&gt;',
			)
		);
		$this->interactivity->config( 'myPlugin', array( 'chars' => '&<>/' ) );

		$interactivity_data_markup = get_echo( array( $this->interactivity, 'print_client_interactivity_data' ) );
		preg_match( '~<script type="application/json" id="wp-interactivity-data">\s*(\{.*\})\s*</script>~s', $interactivity_data_markup, $interactivity_data_string );

		$expected = <<<"JSON"
{"config":{"myPlugin":{"chars":"&\\u003C\\u003E/"}},"state":{"myPlugin":{"ampersand":"&","less-than sign":"\\u003C","greater-than sign":"\\u003E","solidus":"/","line separator":"\u{2028}","paragraph separator":"\u{2029}","flag of england":"\u{1F3F4}\u{E0067}\u{E0062}\u{E0065}\u{E006E}\u{E0067}\u{E007F}","malicious script closer":"\\u003C/script\\u003E","entity-encoded malicious script closer":"&lt;/script&gt;"}}}
JSON;
		$this->assertEquals( $expected, $interactivity_data_string[1] );
	}

	/**
	 * Tests that special characters in the initial state and configuration are
	 * properly escaped when the blog_charset is not UTF-8 (unicode compatible).
	 *
	 * This this test, unicode and line terminators should be escaped to their
	 * JSON unicode sequences.
	 *
	 * @ticket 61170
	 *
	 * @covers ::state
	 * @covers ::config
	 * @covers ::print_client_interactivity_data
	 */
	public function test_state_and_config_escape_special_characters_non_utf8() {
		add_filter( 'pre_option_blog_charset', array( $this, 'charset_iso_8859_1' ) );
		$this->interactivity->state(
			'myPlugin',
			array(
				'ampersand'                              => '&',
				'less-than sign'                         => '<',
				'greater-than sign'                      => '>',
				'solidus'                                => '/',
				'line separator'                         => "\u{2028}",
				'paragraph separator'                    => "\u{2029}",
				'flag of england'                        => "\u{1F3F4}\u{E0067}\u{E0062}\u{E0065}\u{E006E}\u{E0067}\u{E007F}",
				'malicious script closer'                => '</script>',
				'entity-encoded malicious script closer' => '&lt;/script&gt;',
			)
		);
		$this->interactivity->config( 'myPlugin', array( 'chars' => '&<>/' ) );

		$interactivity_data_markup = get_echo( array( $this->interactivity, 'print_client_interactivity_data' ) );
		preg_match( '~<script type="application/json" id="wp-interactivity-data">\s*(\{.*\})\s*</script>~s', $interactivity_data_markup, $interactivity_data_string );

		$expected = <<<"JSON"
{"config":{"myPlugin":{"chars":"&\\u003C\\u003E/"}},"state":{"myPlugin":{"ampersand":"&","less-than sign":"\\u003C","greater-than sign":"\\u003E","solidus":"/","line separator":"\\u2028","paragraph separator":"\\u2029","flag of england":"\\ud83c\\udff4\\udb40\\udc67\\udb40\\udc62\\udb40\\udc65\\udb40\\udc6e\\udb40\\udc67\\udb40\\udc7f","malicious script closer":"\\u003C/script\\u003E","entity-encoded malicious script closer":"&lt;/script&gt;"}}}
JSON;
		$this->assertEquals( $expected, $interactivity_data_string[1] );
	}

	/**
	 * Test that calling state without a namespace arg returns the state data
	 * for the current namespace in the internal namespace stack.
	 *
	 * @ticket 61037
	 *
	 * @covers ::state
	 */
	public function test_state_without_namespace() {
		$this->set_internal_namespace_stack( 'myPlugin' );

		$this->interactivity->state( 'myPlugin', array( 'a' => 1 ) );
		$this->interactivity->state( 'otherPlugin', array( 'b' => 2 ) );

		$this->assertEquals(
			array( 'a' => 1 ),
			$this->interactivity->state()
		);
	}

	/**
	 * Test that passing state data without a valid namespace does nothing and
	 * just returns an empty array.
	 *
	 * @ticket 61037
	 *
	 * @covers ::state
	 * @expectedIncorrectUsage WP_Interactivity_API::state
	 */
	public function test_state_with_data_and_invalid_namespace() {
		$this->set_internal_namespace_stack( 'myPlugin' );

		$this->interactivity->state( 'myPlugin', array( 'a' => 1 ) );
		$this->interactivity->state( 'otherPlugin', array( 'b' => 2 ) );

		$this->assertEquals(
			array(),
			$this->interactivity->state( null, array( 'newProp' => 'value' ) )
		);
	}

	/**
	 * Test that calling state with an empty string as namespace is not allowed.
	 *
	 * @ticket 61037
	 *
	 * @covers ::state
	 * @expectedIncorrectUsage WP_Interactivity_API::state
	 */
	public function test_state_with_empty_string_as_namespace() {
		$this->set_internal_namespace_stack( 'myPlugin' );

		$this->interactivity->state( 'myPlugin', array( 'a' => 1 ) );
		$this->interactivity->state( 'otherPlugin', array( 'b' => 2 ) );

		$this->assertEquals(
			array(),
			$this->interactivity->state( '' )
		);
	}

	/**
	 * Tests that calling state without namespace outside of
	 * `process_directives` execution is not allowed.
	 *
	 * @ticket 61037
	 *
	 * @covers ::state
	 * @expectedIncorrectUsage WP_Interactivity_API::state
	 */
	public function test_state_without_namespace_outside_directive_processing() {
		$this->assertEquals(
			array(),
			$this->interactivity->state()
		);
	}

	/**
	 * Test that `get_context` returns the latest context value for the given
	 * namespace.
	 *
	 * @ticket 61037
	 *
	 * @covers ::get_context
	 */
	public function test_get_context_with_namespace() {
		$this->set_internal_namespace_stack( 'myPlugin' );
		$this->set_internal_context_stack(
			array(
				'myPlugin' => array( 'a' => 0 ),
			),
			array(
				'myPlugin'    => array( 'a' => 1 ),
				'otherPlugin' => array( 'b' => 2 ),
			)
		);

		$this->assertEquals(
			array( 'a' => 1 ),
			$this->interactivity->get_context( 'myPlugin' )
		);
		$this->assertEquals(
			array( 'b' => 2 ),
			$this->interactivity->get_context( 'otherPlugin' )
		);
	}

	/**
	 * Test that `get_context` uses the current namespace in the internal
	 * namespace stack when the parameter is omitted.
	 *
	 * @ticket 61037
	 *
	 * @covers ::get_context
	 */
	public function test_get_context_without_namespace() {
		$this->set_internal_namespace_stack( 'myPlugin' );
		$this->set_internal_context_stack(
			array(
				'myPlugin' => array( 'a' => 0 ),
			),
			array(
				'myPlugin'    => array( 'a' => 1 ),
				'otherPlugin' => array( 'b' => 2 ),
			)
		);

		$this->assertEquals(
			array( 'a' => 1 ),
			$this->interactivity->get_context()
		);
	}

	/**
	 * Test that `get_context` returns an empty array when the context stack is
	 * empty.
	 *
	 * @ticket 61037
	 *
	 * @covers ::get_context
	 */
	public function test_get_context_with_empty_context_stack() {
		$this->set_internal_namespace_stack( 'myPlugin' );
		$this->set_internal_context_stack();

		$this->assertEquals(
			array(),
			$this->interactivity->get_context( 'myPlugin' )
		);
	}

	/**
	 * Test that `get_context` returns an empty array if the given namespace is
	 * not defined.
	 *
	 * @ticket 61037
	 *
	 * @covers ::get_context
	 */
	public function test_get_context_with_undefined_namespace() {
		$this->set_internal_namespace_stack( 'myPlugin' );
		$this->set_internal_context_stack(
			array(
				'myPlugin' => array( 'a' => 0 ),
			),
			array(
				'myPlugin' => array( 'a' => 1 ),
			)
		);

		$this->assertEquals(
			array(),
			$this->interactivity->get_context( 'otherPlugin' )
		);
	}

	/**
	 * Test that `get_context` should not be called with an empty string.
	 *
	 * @ticket 61037
	 *
	 * @covers ::get_context
	 * @expectedIncorrectUsage WP_Interactivity_API::get_context
	 */
	public function test_get_context_with_empty_namespace() {
		$this->set_internal_namespace_stack( 'myPlugin' );
		$this->set_internal_context_stack(
			array(
				'myPlugin' => array( 'a' => 0 ),
			),
			array(
				'myPlugin' => array( 'a' => 1 ),
			)
		);

		$this->assertEquals(
			array(),
			$this->interactivity->get_context( '' )
		);
	}


	/**
	 * Tests that `get_context` should not be called outside of
	 * `process_directives` execution.
	 *
	 * @ticket 61037
	 *
	 * @covers ::get_context
	 * @expectedIncorrectUsage WP_Interactivity_API::get_context
	 */
	public function test_get_context_outside_of_directive_processing() {
		$context = $this->interactivity->get_context();
		$this->assertEquals( array(), $context );
	}

	/**
	 * Tests extracting directive values from different string formats.
	 *
	 * @ticket 60356
	 *
	 * @covers ::extract_directive_value
	 */
	public function test_extract_directive_value() {
		$extract_directive_value = new ReflectionMethod( $this->interactivity, 'extract_directive_value' );
		$extract_directive_value->setAccessible( true );

		$result = $extract_directive_value->invoke( $this->interactivity, 'state.foo', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', 'state.foo' ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, 'otherPlugin::state.foo', 'myPlugin' );
		$this->assertEquals( array( 'otherPlugin', 'state.foo' ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, '{ "isOpen": false }', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', array( 'isOpen' => false ) ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, 'otherPlugin::{ "isOpen": false }', 'myPlugin' );
		$this->assertEquals( array( 'otherPlugin', array( 'isOpen' => false ) ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, 'true', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', true ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, 'false', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', false ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, 'null', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', null ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, '100', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', 100 ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, '1.2', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', 1.2 ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, '1.2.3', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', '1.2.3' ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, 'otherPlugin::true', 'myPlugin' );
		$this->assertEquals( array( 'otherPlugin', true ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, 'otherPlugin::false', 'myPlugin' );
		$this->assertEquals( array( 'otherPlugin', false ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, 'otherPlugin::null', 'myPlugin' );
		$this->assertEquals( array( 'otherPlugin', null ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, 'otherPlugin::100', 'myPlugin' );
		$this->assertEquals( array( 'otherPlugin', 100 ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, 'otherPlugin::1.2', 'myPlugin' );
		$this->assertEquals( array( 'otherPlugin', 1.2 ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, 'otherPlugin::1.2.3', 'myPlugin' );
		$this->assertEquals( array( 'otherPlugin', '1.2.3' ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, 'otherPlugin::[{"o":4}, null, 3e6]', 'myPlugin' );
		$this->assertEquals( array( 'otherPlugin', array( array( 'o' => 4 ), null, 3000000.0 ) ), $result );
	}

	/**
	 * Tests extracting directive values with empty or invalid input.
	 *
	 * @ticket 60356
	 *
	 * @covers ::extract_directive_value
	 */
	public function test_extract_directive_value_empty_values() {
		$extract_directive_value = new ReflectionMethod( $this->interactivity, 'extract_directive_value' );
		$extract_directive_value->setAccessible( true );

		$result = $extract_directive_value->invoke( $this->interactivity, '', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', null ), $result );

		// This is a boolean attribute.
		$result = $extract_directive_value->invoke( $this->interactivity, true, 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', null ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, false, 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', null ), $result );

		$result = $extract_directive_value->invoke( $this->interactivity, null, 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', null ), $result );

		// A string ending in `::` without any extra characters is not considered a
		// namespace.
		$result = $extract_directive_value->invoke( $this->interactivity, 'myPlugin::', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', 'myPlugin::' ), $result );

		// A namespace with invalid characters is not considered a valid namespace.
		$result = $extract_directive_value->invoke( $this->interactivity, '$myPlugin::state.foo', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', '$myPlugin::state.foo' ), $result );
	}

	/**
	 * Tests extracting directive values from invalid JSON strings.
	 *
	 * @ticket 60356
	 *
	 * @covers ::extract_directive_value
	 */
	public function test_extract_directive_value_invalid_json() {
		$extract_directive_value = new ReflectionMethod( $this->interactivity, 'extract_directive_value' );
		$extract_directive_value->setAccessible( true );

		// Invalid JSON due to missing quotes. Returns the original value.
		$result = $extract_directive_value->invoke( $this->interactivity, '{ isOpen: false }', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', '{ isOpen: false }' ), $result );

		// Null string. Returns null.
		$result = $extract_directive_value->invoke( $this->interactivity, 'null', 'myPlugin' );
		$this->assertEquals( array( 'myPlugin', null ), $result );
	}

	/**
	 * Tests the ability to extract prefix and suffix from a directive attribute
	 * name.
	 *
	 * @ticket 60356
	 *
	 * @covers ::extract_prefix_and_suffix
	 */
	public function test_extract_prefix_and_suffix() {
		$extract_prefix_and_suffix = new ReflectionMethod( $this->interactivity, 'extract_prefix_and_suffix' );
		$extract_prefix_and_suffix->setAccessible( true );

		$result = $extract_prefix_and_suffix->invoke( $this->interactivity, 'data-wp-interactive' );
		$this->assertEquals( array( 'data-wp-interactive' ), $result );

		$result = $extract_prefix_and_suffix->invoke( $this->interactivity, 'data-wp-bind--src' );
		$this->assertEquals( array( 'data-wp-bind', 'src' ), $result );

		$result = $extract_prefix_and_suffix->invoke( $this->interactivity, 'data-wp-foo--and--bar' );
		$this->assertEquals( array( 'data-wp-foo', 'and--bar' ), $result );
	}

	/**
	 * Tests that the `process_directives` method doesn't change the HTML if it
	 * doesn't contain directives.
	 *
	 * @ticket 60356
	 *
	 * @covers ::process_directives
	 */
	public function test_process_directives_do_nothing_without_directives() {
		$html           = '<div>Inner content here</div>';
		$processed_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( $html, $processed_html );

		$html           = '<div><span>Content</span><strong>More Content</strong></div>';
		$processed_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( $html, $processed_html );
	}

	/**
	 * Tests that the `process_directives` method changes the HTML if it contains
	 * directives.
	 *
	 * @ticket 60356
	 *
	 * @covers ::process_directives
	 */
	public function test_process_directives_changes_html_with_balanced_tags() {
		$this->interactivity->state( 'myPlugin', array( 'id' => 'some-id' ) );
		$html           = '<div data-wp-bind--id="myPlugin::state.id">Inner content</div>';
		$processed_html = $this->interactivity->process_directives( $html );
		$p              = new WP_HTML_Tag_Processor( $processed_html );
		$p->next_tag();
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
	}

	/**
	 * Tests how `process_directives` handles HTML with unknown directives.
	 *
	 * @ticket 60356
	 *
	 * @covers ::process_directives
	 */
	public function test_process_directives_doesnt_fail_with_unknown_directives() {
		$html           = '<div data-wp-unknown="">Text</div>';
		$processed_html = $this->interactivity->process_directives( $html );
		$this->assertEquals( $html, $processed_html );
	}

	/**
	 * Tests that directives are processed in the correct order.
	 *
	 * @ticket 60356
	 *
	 * @covers ::process_directives
	 */
	public function test_process_directives_process_the_directives_in_the_correct_order() {
		$html           = '
			<div
				data-wp-interactive=\'{ "namespace": "test" }\'
				data-wp-context=\'{ "isClass": true, "id": "some-id", "text": "Updated", "display": "none" }\'
				data-wp-bind--id="context.id"
				data-wp-class--some-class="context.isClass"
				data-wp-style--display="context.display"
				data-wp-text="context.text"
			>Text</div>';
		$processed_html = $this->interactivity->process_directives( $html );
		$p              = new WP_HTML_Tag_Processor( $processed_html );
		$p->next_tag();
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
		$this->assertEquals( 'some-class', $p->get_attribute( 'class' ) );
		$this->assertEquals( 'display:none;', $p->get_attribute( 'style' ) );
		$this->assertStringContainsString( 'Updated', $p->get_updated_html() );
		$this->assertStringNotContainsString( 'Text', $p->get_updated_html() );
	}

	/**
	 * Tests that the `process_directives` returns the same HTML if it contains
	 * unbalanced tags.
	 *
	 * @ticket 60356
	 *
	 * @covers ::process_directives
	 *
	 * @dataProvider data_html_with_unbalanced_tags
	 *
	 * @expectedIncorrectUsage WP_Interactivity_API::_process_directives
	 *
	 * @param string $html HTML containing unbalanced tags and also a directive.
	 */
	public function test_process_directives_doesnt_change_html_if_contains_unbalanced_tags( $html ) {
		$this->interactivity->state( 'myPlugin', array( 'id' => 'some-id' ) );

		$processed_html = $this->interactivity->process_directives( $html );
		$p              = new WP_HTML_Tag_Processor( $processed_html );
		$p->next_tag();
		$this->assertNull( $p->get_attribute( 'id' ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array[].
	 */
	public static function data_html_with_unbalanced_tags() {
		return array(
			'DIV closer after'   => array( '<div data-wp-bind--id="myPlugin::state.id">Inner content</div></div>' ),
			'DIV opener after'   => array( '<div data-wp-bind--id="myPlugin::state.id">Inner content</div><div>' ),
			'DIV opener before'  => array( '<div><div data-wp-bind--id="myPlugin::state.id">Inner content</div>' ),
			'DIV closer before'  => array( '</div><div data-wp-bind--id="myPlugin::state.id">Inner content</div>' ),
			'DIV opener inside'  => array( '<div data-wp-bind--id="myPlugin::state.id">Inner<div>content</div>' ),
			'DIV closer inside'  => array( '<div data-wp-bind--id="myPlugin::state.id">Inner</div>content</div>' ),
			'SPAN opener inside' => array( '<div data-wp-bind--id="myPlugin::state.id"><span>Inner content</div>' ),
			'SPAN closer after'  => array( '<div data-wp-bind--id="myPlugin::state.id">Inner content</div></span>' ),
			'SPAN overlapping'   => array( '<div data-wp-bind--id="myPlugin::state.id"><span>Inner content</div></span>' ),
		);
	}

	/**
	 * Tests that the `process_directives` process the HTML outside a SVG tag.
	 *
	 * @ticket 60517
	 *
	 * @covers ::process_directives
	 */
	public function test_process_directives_changes_html_if_contains_svgs() {
		$this->interactivity->state(
			'myPlugin',
			array(
				'id'    => 'some-id',
				'width' => '100',
			)
		);
		$html           = '
			<header>
				<svg height="100">
					<title>Red Circle</title>
					<circle cx="50" cy="50" r="40" stroke="black" stroke-width="3" fill="red" />
				</svg>
				<div data-wp-bind--id="myPlugin::state.id"></div>
			</header>
		';
		$processed_html = $this->interactivity->process_directives( $html );
		$p              = new WP_HTML_Tag_Processor( $processed_html );
		$p->next_tag( 'div' );
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
	}

	/**
	 * Tests that the `process_directives` does not process the HTML
	 * inside SVG tags.
	 *
	 * @ticket 60517
	 *
	 * @covers ::process_directives
	 * @expectedIncorrectUsage WP_Interactivity_API_Directives_Processor::skip_to_tag_closer
	 */
	public function test_process_directives_does_not_change_inner_html_in_svgs() {
		$this->interactivity->state(
			'myPlugin',
			array(
				'id' => 'some-id',
			)
		);
		$html           = '
			<header>
				<svg height="100">
					<circle cx="50" cy="50" r="40" stroke="black" stroke-width="3" fill="red" />
					<g data-wp-bind--id="myPlugin::state.id" />
				</svg>
			</header>
		';
		$processed_html = $this->interactivity->process_directives( $html );
		$p              = new WP_HTML_Tag_Processor( $processed_html );
		$p->next_tag( 'div' );
		$this->assertNull( $p->get_attribute( 'id' ) );
	}

	/**
	 * Tests that the `process_directives` process the HTML outside the
	 * MathML tag.
	 *
	 * @ticket 60517
	 *
	 * @covers ::process_directives
	 * @expectedIncorrectUsage WP_Interactivity_API::_process_directives
	 */
	public function test_process_directives_change_html_if_contains_math() {
		$this->interactivity->state(
			'myPlugin',
			array(
				'id'   => 'some-id',
				'math' => 'ml-id',
			)
		);
		$html           = '
			<header>
				<math data-wp-bind--id="myPlugin::state.math">
					<mi>x</mi>
					<mo>=</mo>
					<mi>1</mi>
				</math>
				<div data-wp-bind--id="myPlugin::state.id"></div>
			</header>
		';
		$processed_html = $this->interactivity->process_directives( $html );
		$p              = new WP_HTML_Tag_Processor( $processed_html );
		$p->next_tag( 'math' );
		$this->assertNull( $p->get_attribute( 'id' ) );
		$p->next_tag( 'div' );
		$this->assertEquals( 'some-id', $p->get_attribute( 'id' ) );
	}

	/**
	 * Tests that the `process_directives` does not process the HTML
	 * inside MathML tags.
	 *
	 * @ticket 60517
	 *
	 * @covers ::process_directives
	 * @expectedIncorrectUsage WP_Interactivity_API::_process_directives
	 * @expectedIncorrectUsage WP_Interactivity_API_Directives_Processor::skip_to_tag_closer
	 */
	public function test_process_directives_does_not_change_inner_html_in_math() {
		$this->interactivity->state(
			'myPlugin',
			array(
				'id' => 'some-id',
			)
		);
		$html           = '
			<header>
				<math data-wp-bind--id="myPlugin::state.math">
					<mrow data-wp-bind--id="myPlugin::state.id" />
					<mi>x</mi>
					<mo>=</mo>
					<mi>1</mi>
				</math>
			</header>
		';
		$processed_html = $this->interactivity->process_directives( $html );
		$p              = new WP_HTML_Tag_Processor( $processed_html );
		$p->next_tag( 'div' );
		$this->assertNull( $p->get_attribute( 'id' ) );
	}

	/**
	 * Invokes the private `evaluate` method of WP_Interactivity_API class.
	 *
	 * @param string $directive_value   The directive attribute value to evaluate.
	 * @return mixed The result of the evaluate method.
	 */
	private function evaluate( $directive_value ) {
		/*
		 * The global WP_Interactivity_API instance is momentarily replaced to
		 * make global functions like `wp_interactivity_state` and
		 * `wp_interactivity_get_config` work as expected.
		 */
		global $wp_interactivity;
		$wp_interactivity_prev = $wp_interactivity;
		$wp_interactivity      = $this->interactivity;

		$evaluate = new ReflectionMethod( $this->interactivity, 'evaluate' );
		$evaluate->setAccessible( true );

		$result = $evaluate->invokeArgs( $this->interactivity, array( $directive_value ) );

		// Restore the original WP_Interactivity_API instance.
		$wp_interactivity = $wp_interactivity_prev;

		return $result;
	}

	/**
	 * Tests that the `evaluate` method operates correctly for valid expressions.
	 *
	 * @ticket 60356
	 *
	 * @covers ::evaluate
	 */
	public function test_evaluate_value() {
		$obj       = new stdClass();
		$obj->prop = 'object property';
		$this->interactivity->state(
			'myPlugin',
			array(
				'key'       => 'myPlugin-state',
				'obj'       => $obj,
				'arrAccess' => new class() implements ArrayAccess {
					public function offsetExists( $offset ): bool {
						return true;
					}

					#[\ReturnTypeWillChange]
					public function offsetGet( $offset ) {
						return $offset;
					}

					public function offsetSet( $offset, $value ): void {}

					public function offsetUnset( $offset ): void {}
				},
			)
		);
		$this->interactivity->state( 'otherPlugin', array( 'key' => 'otherPlugin-state' ) );
		$this->set_internal_context_stack(
			array(
				'myPlugin'    => array( 'key' => 'myPlugin-context' ),
				'otherPlugin' => array( 'key' => 'otherPlugin-context' ),
			)
		);
		$this->set_internal_namespace_stack( 'myPlugin' );

		$result = $this->evaluate( 'state.key' );
		$this->assertEquals( 'myPlugin-state', $result );

		$result = $this->evaluate( 'context.key' );
		$this->assertEquals( 'myPlugin-context', $result );

		$result = $this->evaluate( 'otherPlugin::state.key' );
		$this->assertEquals( 'otherPlugin-state', $result );

		$result = $this->evaluate( 'otherPlugin::context.key' );
		$this->assertEquals( 'otherPlugin-context', $result );

		$result = $this->evaluate( 'state.obj.prop' );
		$this->assertSame( 'object property', $result );

		$result = $this->evaluate( 'state.arrAccess.1' );
		$this->assertSame( '1', $result );
	}

	/**
	 * Tests that the `evaluate` method operates correctly when used with the
	 * negation operator (!).
	 *
	 * @ticket 60356
	 *
	 * @covers ::evaluate
	 */
	public function test_evaluate_value_negation() {
		$this->interactivity->state( 'myPlugin', array( 'key' => 'myPlugin-state' ) );
		$this->interactivity->state( 'otherPlugin', array( 'key' => 'otherPlugin-state' ) );
		$this->set_internal_context_stack(
			array(
				'myPlugin'    => array( 'key' => 'myPlugin-context' ),
				'otherPlugin' => array( 'key' => 'otherPlugin-context' ),
			)
		);
		$this->set_internal_namespace_stack( 'myPlugin' );

		$result = $this->evaluate( '!state.key' );
		$this->assertFalse( $result );

		$result = $this->evaluate( '!context.key' );
		$this->assertFalse( $result );

		$result = $this->evaluate( 'otherPlugin::!state.key' );
		$this->assertFalse( $result );

		$result = $this->evaluate( 'otherPlugin::!context.key' );
		$this->assertFalse( $result );
	}

	/**
	 * Tests the `evaluate` method with non-existent paths.
	 *
	 * @ticket 60356
	 *
	 * @covers ::evaluate
	 */
	public function test_evaluate_non_existent_path() {
		$this->interactivity->state( 'myPlugin', array( 'key' => 'myPlugin-state' ) );
		$this->interactivity->state( 'otherPlugin', array( 'key' => 'otherPlugin-state' ) );
		$this->set_internal_context_stack(
			array(
				'myPlugin'    => array( 'key' => 'myPlugin-context' ),
				'otherPlugin' => array( 'key' => 'otherPlugin-context' ),
			)
		);
		$this->set_internal_namespace_stack( 'myPlugin' );

		$result = $this->evaluate( 'state.nonExistentKey' );
		$this->assertNull( $result );

		$result = $this->evaluate( 'context.nonExistentKey' );
		$this->assertNull( $result );

		$result = $this->evaluate( 'otherPlugin::state.nonExistentKey' );
		$this->assertNull( $result );

		$result = $this->evaluate( 'otherPlugin::context.nonExistentKey' );
		$this->assertNull( $result );

		$result = $this->evaluate( ' state.key' ); // Extra space.
		$this->assertNull( $result );

		$result = $this->evaluate( 'otherPlugin:: state.key' ); // Extra space.
		$this->assertNull( $result );
	}

	/**
	 * Tests the `evaluate` method for retrieving nested values.
	 *
	 * @ticket 60356
	 *
	 * @covers ::evaluate
	 */
	public function test_evaluate_nested_value() {
		$this->interactivity->state(
			'myPlugin',
			array(
				'nested' => array( 'key' => 'myPlugin-state-nested' ),
			)
		);
		$this->interactivity->state(
			'otherPlugin',
			array(
				'nested' => array( 'key' => 'otherPlugin-state-nested' ),
			)
		);
		$this->set_internal_context_stack(
			array(
				'myPlugin'    => array(
					'nested' => array( 'key' => 'myPlugin-context-nested' ),
				),
				'otherPlugin' => array(
					'nested' => array( 'key' => 'otherPlugin-context-nested' ),
				),
			)
		);
		$this->set_internal_namespace_stack( 'myPlugin' );

		$result = $this->evaluate( 'state.nested.key' );
		$this->assertEquals( 'myPlugin-state-nested', $result );

		$result = $this->evaluate( 'context.nested.key' );
		$this->assertEquals( 'myPlugin-context-nested', $result );

		$result = $this->evaluate( 'otherPlugin::state.nested.key' );
		$this->assertEquals( 'otherPlugin-state-nested', $result );

		$result = $this->evaluate( 'otherPlugin::context.nested.key' );
		$this->assertEquals( 'otherPlugin-context-nested', $result );
	}

	/**
	 * Tests the `evaluate` method for non valid namespace values.
	 *
	 * @ticket 61044
	 *
	 * @covers ::evaluate
	 * @expectedIncorrectUsage WP_Interactivity_API::evaluate
	 */
	public function test_evaluate_unvalid_namespaces() {
		$this->set_internal_context_stack( array() );
		$this->set_internal_namespace_stack();

		$result = $this->evaluate( 'path', 'null' );
		$this->assertNull( $result );

		$result = $this->evaluate( 'path', '' );
		$this->assertNull( $result );

		$result = $this->evaluate( 'path', '{}' );
		$this->assertNull( $result );
	}

	/**
	 * Tests the `evaluate` method for derived state functions.
	 *
	 * @ticket 61037
	 *
	 * @covers ::evaluate
	 * @covers wp_interactivity_state
	 * @covers wp_interactivity_get_context
	 */
	public function test_evaluate_derived_state() {
		$this->interactivity->state(
			'myPlugin',
			array(
				'key'     => 'myPlugin-state',
				'derived' => function () {
					$state   = wp_interactivity_state();
					$context = wp_interactivity_get_context();
					return 'Derived state: ' .
						$state['key'] .
						"\n" .
						'Derived context: ' .
						$context['key'];
				},
			)
		);
		$this->set_internal_context_stack(
			array(
				'myPlugin' => array(
					'key' => 'myPlugin-context',
				),
			)
		);
		$this->set_internal_namespace_stack( 'myPlugin' );

		$result = $this->evaluate( 'state.derived' );
		$this->assertSame( "Derived state: myPlugin-state\nDerived context: myPlugin-context", $result );
	}

	/**
	 * Tests the `evaluate` method for derived state functions accessing a
	 * different namespace.
	 *
	 * @ticket 61037
	 *
	 * @covers ::evaluate
	 * @covers wp_interactivity_state
	 * @covers wp_interactivity_get_context
	 */
	public function test_evaluate_derived_state_accessing_different_namespace() {
		$this->interactivity->state(
			'myPlugin',
			array(
				'key'     => 'myPlugin-state',
				'derived' => function () {
					$state   = wp_interactivity_state( 'otherPlugin' );
					$context = wp_interactivity_get_context( 'otherPlugin' );
					return 'Derived state: ' .
						$state['key'] .
						"\n" .
						'Derived context: ' .
						$context['key'];
				},
			)
		);
		$this->interactivity->state( 'otherPlugin', array( 'key' => 'otherPlugin-state' ) );
		$this->set_internal_context_stack(
			array(
				'myPlugin'    => array(
					'key' => 'myPlugin-context',
				),
				'otherPlugin' => array(
					'key' => 'otherPlugin-context',
				),
			)
		);
		$this->set_internal_namespace_stack( 'myPlugin' );

		$result = $this->evaluate( 'state.derived' );
		$this->assertSame( "Derived state: otherPlugin-state\nDerived context: otherPlugin-context", $result );
	}

	/**
	 * Tests the `evaluate` method for derived state functions defined in a
	 * different namespace.
	 *
	 * @ticket 61037
	 *
	 * @covers ::evaluate
	 * @covers wp_interactivity_state
	 * @covers wp_interactivity_get_context
	 */
	public function test_evaluate_derived_state_defined_in_different_namespace() {
		$this->interactivity->state( 'myPlugin', array( 'key' => 'myPlugin-state' ) );
		$this->interactivity->state(
			'otherPlugin',
			array(
				'key'     => 'otherPlugin-state',
				'derived' => function () {
					$state   = wp_interactivity_state();
					$context = wp_interactivity_get_context();
					return 'Derived state: ' .
						$state['key'] .
						"\n" .
						'Derived context: ' .
						$context['key'];
				},
			)
		);
		$this->set_internal_context_stack(
			array(
				'myPlugin'    => array(
					'key' => 'myPlugin-context',
				),
				'otherPlugin' => array(
					'key' => 'otherPlugin-context',
				),
			)
		);
		$this->set_internal_namespace_stack( 'myPlugin' );

		$result = $this->evaluate( 'otherPlugin::state.derived' );
		$this->assertSame( "Derived state: otherPlugin-state\nDerived context: otherPlugin-context", $result );
	}


	/**
	 * Tests the `evaluate` method for derived state functions that throw.
	 *
	 * @ticket 61037
	 *
	 * @covers ::evaluate
	 * @expectedIncorrectUsage WP_Interactivity_API::evaluate
	 */
	public function test_evaluate_derived_state_that_throws() {
		$this->interactivity->state(
			'myPlugin',
			array(
				'derivedThatThrows' => function () {
					throw new Error( 'Something bad happened.' );
				},
			)
		);
		$this->set_internal_context_stack();
		$this->set_internal_namespace_stack( 'myPlugin' );

		$result = $this->evaluate( 'state.derivedThatThrows' );
		$this->assertNull( $result );
	}

	/**
	 * Tests the `kebab_to_camel_case` method.
	 *
	 * @covers ::kebab_to_camel_case
	 */
	public function test_kebab_to_camel_case() {
		$method = new ReflectionMethod( $this->interactivity, 'kebab_to_camel_case' );
		$method->setAccessible( true );

		$this->assertSame( '', $method->invoke( $this->interactivity, '' ) );
		$this->assertSame( 'item', $method->invoke( $this->interactivity, 'item' ) );
		$this->assertSame( 'myItem', $method->invoke( $this->interactivity, 'my-item' ) );
		$this->assertSame( 'my_item', $method->invoke( $this->interactivity, 'my_item' ) );
		$this->assertSame( 'myItem', $method->invoke( $this->interactivity, 'My-iTem' ) );
		$this->assertSame( 'myItemWithMultipleHyphens', $method->invoke( $this->interactivity, 'my-item-with-multiple-hyphens' ) );
		$this->assertSame( 'myItemWith-DoubleHyphens', $method->invoke( $this->interactivity, 'my-item-with--double-hyphens' ) );
		$this->assertSame( 'myItemWith_underScore', $method->invoke( $this->interactivity, 'my-item-with_under-score' ) );
		$this->assertSame( 'myItem', $method->invoke( $this->interactivity, '-my-item' ) );
		$this->assertSame( 'myItem', $method->invoke( $this->interactivity, 'my-item-' ) );
		$this->assertSame( 'myItem', $method->invoke( $this->interactivity, '-my-item-' ) );
	}
}
