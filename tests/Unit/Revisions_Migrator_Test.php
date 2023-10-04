<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit;

use Brain\Monkey;
use Mockery;
use WP_Post;
use Yoast\WP\Duplicate_Post\Revisions_Migrator;

/**
 * Test the Revisions_Migrator class.
 */
class Revisions_Migrator_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Revisions_Migrator
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	protected function set_up() {
		parent::set_up();

		$this->instance = new Revisions_Migrator();
	}

	/**
	 * Tests the registration of the hooks.
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Revisions_Migrator::register_hooks
	 */
	public function test_register_hooks() {
		Monkey\Actions\expectAdded( 'duplicate_post_after_rewriting' )
			->with( [ $this->instance, 'migrate_revisions' ], 10, 2 );

		$this->instance->register_hooks();
	}

	/**
	 * Tests the migrate_revisions function with unlimited revisions allowed
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Revisions_Migrator::migrate_revisions
	 */
	public function test_migrate_revisions_unlimited() {
		$post_id           = 128;
		$original_id       = 64;
		$post              = Mockery::mock( WP_Post::class );
		$original_post     = Mockery::mock( WP_Post::class );
		$original_post->ID = $original_id;
		$revisions         = [
			Mockery::mock( WP_Post::class ),
			Mockery::mock( WP_Post::class ),
			Mockery::mock( WP_Post::class ),
			Mockery::mock( WP_Post::class ),
		];

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post, $original_post );

		Monkey\Functions\expect( '\wp_revisions_enabled' )
			->with( $original_post )
			->andReturnTrue();

		Monkey\Functions\expect( '\wp_get_post_revisions' )
			->with( $post )
			->andReturn( $revisions );

		Monkey\Functions\expect( '\wp_update_post' )
			->times( 4 );

		Monkey\Functions\expect( '\wp_revisions_to_keep' )
			->with( $original_post )
			->andReturn( -1 );

		Monkey\Functions\expect( '\wp_delete_post_revision' )
			->never();

		$this->instance->migrate_revisions( $post_id, $original_id );
	}

	/**
	 * Tests the migrate_revisions function with limited revisions allowed
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Revisions_Migrator::migrate_revisions
	 */
	public function test_migrate_revisions_limited() {
		$post_id             = 128;
		$original_id         = 64;
		$post                = Mockery::mock( WP_Post::class );
		$revision            = Mockery::mock( WP_Post::class );
		$revision->ID        = 123;
		$revision->post_name = 'revision';
		$original_post       = Mockery::mock( WP_Post::class );
		$original_post->ID   = $original_id;
		$revisions           = [
			$revision,
			$revision,
			$revision,
			$revision,
		];
		$original_revisions  = [
			$revision,
			$revision,
			$revision,
			$revision,
			$revision,
			$revision,
			$revision,
			$revision,
			$revision,
		];
		$revisions_to_keep   = 6;

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post, $original_post );

		Monkey\Functions\expect( '\wp_revisions_enabled' )
			->with( $original_post )
			->andReturnTrue();

		Monkey\Functions\expect( '\wp_get_post_revisions' )
			->andReturn( $revisions, $original_revisions );

		Monkey\Functions\expect( '\wp_update_post' )
			->times( 4 );

		Monkey\Functions\expect( '\wp_revisions_to_keep' )
			->with( $original_post )
			->andReturn( $revisions_to_keep );

		Monkey\Functions\expect( '\wp_delete_post_revision' )
			->times( 3 );

		$this->instance->migrate_revisions( $post_id, $original_id );
	}

	/**
	 * Tests the migrate_revisions function with no revisions allowed
	 *
	 * @covers \Yoast\WP\Duplicate_Post\Revisions_Migrator::migrate_revisions
	 */
	public function test_migrate_revisions_none() {
		$post_id       = 128;
		$original_id   = 64;
		$post          = Mockery::mock( WP_Post::class );
		$original_post = Mockery::mock( WP_Post::class );

		Monkey\Functions\expect( '\get_post' )
			->andReturn( $post, $original_post );

		Monkey\Functions\expect( '\wp_revisions_enabled' )
			->with( $original_post )
			->andReturnFalse();

		Monkey\Functions\expect( '\wp_get_post_revisions' )
			->never();

		Monkey\Functions\expect( '\wp_update_post' )
			->never();

		Monkey\Functions\expect( '\wp_revisions_to_keep' )
			->never();

		Monkey\Functions\expect( '\wp_delete_post_revision' )
			->never();

		$this->instance->migrate_revisions( $post_id, $original_id );
	}
}
