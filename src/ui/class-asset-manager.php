<?php
/**
 * Duplicate Post class to manage assets.
 *
 * @package Duplicate_Post
 */

namespace Yoast\WP\Duplicate_Post\UI;

use Yoast\WP\Duplicate_Post\Utils;

/**
 * Represents the Duplicate Post Asset Manager class.
 */
class Asset_Manager {

	/**
	 * Adds hooks to integrate with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'init', [ $this, 'register_styles' ] );
		\add_action( 'init', [ $this, 'register_scripts' ] );
	}

	/**
	 * Registers the styles.
	 *
	 * @return void
	 */
	public function register_styles() {
		\wp_register_style( 'duplicate-post', \plugins_url( '/duplicate-post.css', DUPLICATE_POST_FILE ), [], DUPLICATE_POST_CURRENT_VERSION );
	}

	/**
	 * Registers the scripts.
	 *
	 * @return void
	 */
	public function register_scripts() {
		\wp_register_script(
			'duplicate_post_edit_script',
			\plugins_url( \sprintf( 'js/dist/duplicate-post-edit-%s.js', Utils::flatten_version( DUPLICATE_POST_CURRENT_VERSION ) ), DUPLICATE_POST_FILE ),
			[
				'wp-blocks',
				'wp-element',
				'wp-i18n',
			],
			DUPLICATE_POST_CURRENT_VERSION,
			true
		);

		\wp_register_script(
			'duplicate_post_strings',
			\plugins_url( \sprintf( 'js/dist/duplicate-post-strings-%s.js', Utils::flatten_version( DUPLICATE_POST_CURRENT_VERSION ) ), DUPLICATE_POST_FILE ),
			[
				'wp-element',
				'wp-i18n',
			],
			DUPLICATE_POST_CURRENT_VERSION,
			true
		);

		\wp_register_script(
			'duplicate_post_quick_edit_script',
			\plugins_url( \sprintf( 'js/dist/duplicate-post-quick-edit-%s.js', Utils::flatten_version( DUPLICATE_POST_CURRENT_VERSION ) ), DUPLICATE_POST_FILE ),
			[ 'jquery' ],
			DUPLICATE_POST_CURRENT_VERSION,
			true
		);
	}

	/**
	 * Enqueues the styles.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		\wp_enqueue_style( 'duplicate-post' );
	}

	/**
	 * Enqueues the script for the Block editor and passes object via localization.
	 *
	 * @param array $object The object to pass to the script.
	 *
	 * @return void
	 */
	public function enqueue_edit_script( $object = [] ) {
		$handle = 'duplicate_post_edit_script';
		\wp_enqueue_script( $handle );
		\wp_add_inline_script(
			'duplicate_post_edit_script',
			'let duplicatePostNotices = {};',
			'before'
		);
		\wp_localize_script(
			$handle,
			'duplicatePost',
			$object
		);
	}

	/**
	 * Enqueues the script for the Javascript strings and passes object via localization.
	 *
	 * @param array $object The object to pass to the script.
	 *
	 * @return void
	 */
	public function enqueue_strings_script( $object = [] ) {
		$handle = 'duplicate_post_strings';
		\wp_enqueue_script( $handle );
		\wp_localize_script(
			$handle,
			'duplicatePostStrings',
			$object
		);
	}

	/**
	 * Enqueues the script for the Quick Edit.
	 *
	 * @return void
	 */
	public function enqueue_quick_edit_script() {
		\wp_enqueue_script( 'duplicate_post_quick_edit_script' );
	}

	/**
	 * Enqueues the desired script and passes the object to it.
	 *
	 * @param string $name   The shortcut name for the script handle.
	 * @param array  $object The object to pass to the script.
	 *
	 * @return void
	 */
	public function enqueue_script( $name, $object = [] ) {
		switch ( $name ) {
			case 'edit':
				$this->enqueue_edit_script( $object );
				break;

			case 'strings':
				$this->enqueue_strings_script( $object );
				break;

			case 'quick-edit':
				$this->enqueue_quick_edit_script();
				break;

			default:
				break;
		}
	}
}