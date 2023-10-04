<?php

namespace Yoast\WP\Duplicate_Post\Tests\Unit;

use Brain\Monkey\Functions;
use Mockery;
use WP_Role;
use Yoast\WPTestUtils\BrainMonkey\YoastTestCase;

/**
 * TestCase base class.
 */
abstract class TestCase extends YoastTestCase {

	/**
	 * Mock various roles as WP_Role objects and stub the get_roles() function.
	 *
	 * @return void
	 */
	protected function stub_wp_roles() {

		// Mock roles to use across several tests.
		$role1               = Mockery::mock( WP_Role::class );
		$role1->name         = 'Editor';
		$role1->capabilities = [
			'read'       => 'read',
			'edit_books' => 'edit_books',
			'edit_posts' => 'edit_posts',
		];
		$role1->allows(
			[
				'has_cap'    => true,
				'add_cap'    => null,
				'remove_cap' => null,
			]
		);

		$role2               = Mockery::mock( WP_Role::class );
		$role2->name         = 'Administrator';
		$role2->capabilities = [
			'read'       => 'read',
			'edit_books' => 'edit_books',
			'edit_posts' => 'edit_posts',
		];
		$role2->allows(
			[
				'has_cap'    => false,
				'add_cap'    => null,
				'remove_cap' => null,
			]
		);

		$role3               = Mockery::mock( WP_Role::class );
		$role3->name         = 'Subscriber';
		$role3->capabilities = [];
		$role3->allows(
			[
				'has_cap'    => false,
				'add_cap'    => null,
				'remove_cap' => null,
			]
		);

		$role_objects = [
			'editor'        => $role1,
			'administrator' => $role2,
			'subscriber'    => $role3,
		];

		Functions\stubs(
			[
				'get_role' => static function ( $name ) use ( $role_objects ) {
					return $role_objects[ $name ];
				},
			]
		);
	}
}
