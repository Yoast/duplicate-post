<?php

namespace Yoast\WP\Duplicate_Post;

use WP_Comment;
use WP_Post;

/**
 * Copies block-level Notes from one post to another.
 *
 * @since 4.7
 */
class Notes_Copier {

	/**
	 * Copies Notes and updates their references in the post content.
	 *
	 * @param int     $new_id    The new post ID.
	 * @param WP_Post $post      The original post object.
	 * @param bool    $copy_date Whether to preserve the Note dates.
	 *
	 * @return void
	 */
	public function copy( $new_id, WP_Post $post, $copy_date = false ) {
		$notes = \get_comments(
			[
				'post_id' => $post->ID,
				'type'    => 'note',
				'order'   => 'ASC',
				'orderby' => 'comment_date_gmt',
			],
		);

		$old_id_to_new = [];
		$pending       = $notes;
		while ( ! empty( $pending ) ) {
			$copied_note = false;
			foreach ( $pending as $index => $note ) {
				if ( $note->comment_parent && ! isset( $old_id_to_new[ $note->comment_parent ] ) ) {
					continue;
				}

				$new_note_id = $this->insert_note( $new_id, $note, $old_id_to_new, $copy_date );
				if ( $new_note_id ) {
					$old_id_to_new[ $note->comment_ID ] = $new_note_id;
				}
				unset( $pending[ $index ] );
				$copied_note = true;
			}

			if ( ! $copied_note ) {
				break;
			}
		}

		if ( empty( $old_id_to_new ) ) {
			return;
		}

		$target_content = \get_post_field( 'post_content', $new_id );
		$blocks         = \parse_blocks( $target_content );
		$this->remap_block_note_ids( $blocks, $old_id_to_new );
		$content = \serialize_blocks( $blocks );
		if ( $content !== $target_content ) {
			\wp_update_post(
				[
					'ID'           => $new_id,
					'post_content' => $content,
				],
			);
		}
	}

	/**
	 * Inserts one Note and copies its metadata.
	 *
	 * @param int             $new_id        The new post ID.
	 * @param WP_Comment      $note          The Note to copy.
	 * @param array<int, int> $old_id_to_new The copied Note IDs.
	 * @param bool            $copy_date     Whether to preserve the Note dates.
	 *
	 * @return int The new Note ID, or 0 on failure.
	 */
	private function insert_note( $new_id, WP_Comment $note, array $old_id_to_new, $copy_date ) {
		$parent = 0;
		if ( $note->comment_parent && isset( $old_id_to_new[ $note->comment_parent ] ) ) {
			$parent = $old_id_to_new[ $note->comment_parent ];
		}

		$commentdata = [
			'comment_post_ID'      => $new_id,
			'comment_author'       => $note->comment_author,
			'comment_author_email' => $note->comment_author_email,
			'comment_author_url'   => $note->comment_author_url,
			'comment_content'      => $note->comment_content,
			'comment_type'         => 'note',
			'comment_parent'       => $parent,
			'user_id'              => $note->user_id,
			'comment_author_IP'    => $note->comment_author_IP,
			'comment_agent'        => $note->comment_agent,
			'comment_karma'        => $note->comment_karma,
			'comment_approved'     => $note->comment_approved,
		];

		if ( $copy_date ) {
			$commentdata['comment_date']     = $note->comment_date;
			$commentdata['comment_date_gmt'] = \get_gmt_from_date( $note->comment_date );
		}

		$new_note_id = \wp_insert_comment( $commentdata );
		if ( ! $new_note_id ) {
			return 0;
		}

		$commentmeta = \get_comment_meta( $note->comment_ID );
		foreach ( $commentmeta as $meta_key => $meta_values ) {
			foreach ( $meta_values as $meta_value ) {
				\add_comment_meta( $new_note_id, $meta_key, Utils::recursively_slash_strings( $meta_value ) );
			}
		}

		return $new_note_id;
	}

	/**
	 * Remaps Note IDs in block metadata recursively.
	 *
	 * @param array<int, array{}> $blocks        Parsed blocks.
	 * @param array<int, int>     $old_id_to_new Note ID map.
	 *
	 * @return void
	 */
	private function remap_block_note_ids( array &$blocks, array $old_id_to_new ) {
		foreach ( $blocks as &$block ) {
			if ( isset( $block['attrs']['metadata']['noteId'] ) ) {
				$old_id = (int) $block['attrs']['metadata']['noteId'];
				if ( isset( $old_id_to_new[ $old_id ] ) ) {
					$block['attrs']['metadata']['noteId'] = $old_id_to_new[ $old_id ];
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$this->remap_block_note_ids( $block['innerBlocks'], $old_id_to_new );
			}
		}
		unset( $block );
	}
}
