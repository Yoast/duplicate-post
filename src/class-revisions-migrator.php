<?php
/**
 * Duplicate Post class to migrate revisions from the Rewrite & Republish copy to the original post.
 *
 * @package Duplicate_Post
 * @since 4.0
 */

namespace Yoast\WP\Duplicate_Post;

/**
 * Represents the Revisions Migrator class.
 */
class Revisions_Migrator {

	/**
	 * Adds hooks to integrate with the Post Republisher class.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'duplicate_post_after_rewriting', [ $this, 'migrate_revisions' ], 10, 2 );
	}

	/**
	 * Updates the revisions of the Rewrite & Republish copy to make them revisions of the original.
	 *
	 * It mimics the behaviour of wp_save_post_revision() in wp-includes/revision.php
	 * by deleting the revisions (except autosaves) exceeding the maximum allowed number.
	 *
	 * @param \WP_Post $post          The Rewrite & Republish copy.
	 * @param \WP_Post $original_post The original post which has been rewritten.
	 *
	 * @return void
	 */
	public function migrate_revisions( $post, $original_post ) {
		if ( ! \wp_revisions_enabled( $original_post ) ) {
			return;
		}

		$copy_revisions = \wp_get_post_revisions( $post );
		foreach ( $copy_revisions as $revision ) {
			$revision->post_parent = $original_post->ID;
			$revision->post_name   = "$original_post->ID-revision-v1";
			\wp_update_post( $revision );
		}

		$revisions_to_keep = \wp_revisions_to_keep( $original_post );
		if ( $revisions_to_keep < 0 ) {
			return;
		}

		$revisions = \wp_get_post_revisions( $original_post, [ 'order' => 'ASC' ] );
		$delete    = \count( $revisions ) - $revisions_to_keep;
		if ( $delete < 1 ) {
			return;
		}

		$revisions = \array_slice( $revisions, 0, $delete );

		for ( $i = 0; isset( $revisions[ $i ] ); $i ++ ) {
			if ( \strpos( $revisions[ $i ]->post_name, 'autosave' ) !== false ) {
				continue;
			}
			\wp_delete_post_revision( $revisions[ $i ]->ID );
		}
	}
}
