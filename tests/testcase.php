<?php

namespace Yoast\WP\Duplicate_Post\Tests;

use Brain\Monkey;
use Mockery;
use Yoast\WPTestUtils\BrainMonkey\YoastTestCase;

/**
 * TestCase base class.
 */
abstract class TestCase extends YoastTestCase {

	/**
	 * Holds an array of dummy roles.
	 *
	 * @var array
	 */
	protected $roles;

	/**
	 * Test setup.
	 */
	protected function set_up() {

		parent::set_up();

		$this->stubEscapeFunctions();
		$this->stubTranslationFunctions();

		// Mock roles to use across several tests.
		$role1               = Mockery::mock( 'WP_Role' );
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

		$role2               = Mockery::mock( 'WP_Role' );
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

		$role3               = Mockery::mock( 'WP_Role' );
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

		$this->roles = $role_objects;

		Monkey\Functions\stubs(
			[
				'get_role' => static function ( $name ) use ( $role_objects ) {
					return $role_objects[ $name ];
				},
			]
		);
	}
}
