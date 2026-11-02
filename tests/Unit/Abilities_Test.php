<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit;

use Brain\Monkey;
use Mockery;
use Yoast\WP\Duplicate_Post\Abilities;
use Yoast\WP\Duplicate_Post\Permissions_Helper;
use Yoast\WP\Duplicate_Post\Post_Duplicator;
use Yoast\WP\Duplicate_Post\Post_Republisher;

/**
 * Test the Abilities class.
 */
final class Abilities_Test extends TestCase {

	/**
	 * Holds the post duplicator.
	 *
	 * @var Post_Duplicator|Mockery\Mock
	 */
	protected $post_duplicator;

	/**
	 * Holds the post republisher.
	 *
	 * @var Post_Republisher|Mockery\Mock
	 */
	protected $post_republisher;

	/**
	 * Holds the permissions helper.
	 *
	 * @var Permissions_Helper|Mockery\Mock
	 */
	protected $permissions_helper;

	/**
	 * The instance.
	 *
	 * @var Abilities
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		$this->stubTranslationFunctions();

		$this->post_duplicator    = Mockery::mock( Post_Duplicator::class );
		$this->post_republisher   = Mockery::mock( Post_Republisher::class );
		$this->permissions_helper = Mockery::mock( Permissions_Helper::class );

		$this->instance = new Abilities(
			$this->post_duplicator,
			$this->post_republisher,
			$this->permissions_helper,
		);
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Abilities::__construct
	 *
	 * @return void
	 */
	public function test_constructor() {
		$this->assertInstanceOf(
			Post_Duplicator::class,
			$this->getPropertyValue( $this->instance, 'post_duplicator' ),
		);

		$this->assertInstanceOf(
			Post_Republisher::class,
			$this->getPropertyValue( $this->instance, 'post_republisher' ),
		);

		$this->assertInstanceOf(
			Permissions_Helper::class,
			$this->getPropertyValue( $this->instance, 'permissions_helper' ),
		);
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Abilities::register_hooks
	 *
	 * @return void
	 */
	public function test_register_hooks() {
		Monkey\Actions\expectAdded( 'wp_abilities_api_categories_init' )
			->with( [ $this->instance, 'register_category' ] );

		Monkey\Actions\expectAdded( 'wp_abilities_api_init' )
			->with( [ $this->instance, 'register_abilities' ] );

		$this->instance->register_hooks();
	}

	/**
	 * Tests the registration of the ability category.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Abilities::register_category
	 *
	 * @return void
	 */
	public function test_register_category() {
		Monkey\Functions\expect( 'wp_register_ability_category' )
			->once()
			->with(
				'duplicate-post',
				Mockery::on(
					static function ( $args ) {
						return isset( $args['label'], $args['description'] );
					},
				),
			);

		$this->instance->register_category();
	}

	/**
	 * Tests that all abilities are registered in the plugin's category.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Abilities::register_abilities
	 * @covers \Yoast\WP\Duplicate_Post\Abilities::get_post_id_input_schema
	 * @covers \Yoast\WP\Duplicate_Post\Abilities::get_post_output_schema
	 *
	 * @return void
	 */
	public function test_register_abilities() {
		$registered = [];

		Monkey\Functions\expect( 'wp_register_ability' )
			->times( 4 )
			->andReturnUsing(
				static function ( $name, $args ) use ( &$registered ) {
					$registered[ $name ] = $args;
				},
			);

		$this->instance->register_abilities();

		$this->assertSame(
			[
				'duplicate-post/clone',
				'duplicate-post/copy-to-new-draft',
				'duplicate-post/rewrite',
				'duplicate-post/republish',
			],
			\array_keys( $registered ),
		);

		foreach ( $registered as $name => $args ) {
			$this->assertSame( 'duplicate-post', $args['category'], "{$name} is not in the plugin's category." );
			$this->assertIsCallable( $args['execute_callback'], "{$name} has no execute callback." );
			$this->assertIsCallable( $args['permission_callback'], "{$name} has no permission callback." );
			$this->assertSame( [ 'post_id' ], $args['input_schema']['required'], "{$name} does not require a post ID." );
			$this->assertArrayHasKey( 'post_id', $args['output_schema']['properties'], "{$name} does not return a post ID." );
			$this->assertTrue( $args['meta']['show_in_rest'], "{$name} is not exposed in the REST API." );
		}
	}

	/**
	 * Tests that the republish ability is annotated as destructive and the copy abilities are not.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Abilities::register_abilities
	 *
	 * @return void
	 */
	public function test_register_abilities_annotations() {
		$registered = [];

		Monkey\Functions\expect( 'wp_register_ability' )
			->times( 4 )
			->andReturnUsing(
				static function ( $name, $args ) use ( &$registered ) {
					$registered[ $name ] = $args;
				},
			);

		$this->instance->register_abilities();

		$this->assertFalse( $registered['duplicate-post/clone']['meta']['annotations']['destructive'] );
		$this->assertFalse( $registered['duplicate-post/copy-to-new-draft']['meta']['annotations']['destructive'] );
		$this->assertFalse( $registered['duplicate-post/rewrite']['meta']['annotations']['destructive'] );
		$this->assertTrue( $registered['duplicate-post/republish']['meta']['annotations']['destructive'] );
	}
}
