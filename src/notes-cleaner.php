<?php

namespace Yoast\WP\Duplicate_Post;

/**
 * Removes invalid block-level Note references from a duplicated post.
 *
 * @since 4.7
 */
class Notes_Cleaner {

	/**
	 * Removes Note references that do not belong to the duplicated post.
	 *
	 * @param int $post_id The duplicated post ID.
	 *
	 * @return void
	 */
	public function clean( $post_id ) {
		$valid_note_ids = \array_map(
			'intval',
			(array) \get_comments(
				[
					'post_id' => $post_id,
					'type'    => 'note',
					'fields'  => 'ids',
				],
			),
		);

		$content = \get_post_field( 'post_content', $post_id );
		$blocks  = \parse_blocks( $content );
		$changed = $this->remove_invalid_note_ids( $blocks, $valid_note_ids );

		if ( ! $changed ) {
			return;
		}

		\wp_update_post(
			[
				'ID'           => $post_id,
				'post_content' => \serialize_blocks( $blocks ),
			],
		);
	}

	/**
	 * Removes invalid Note references from blocks recursively.
	 *
	 * @param array<int, array{}> $blocks         Parsed blocks.
	 * @param array<int, int>     $valid_note_ids Note IDs belonging to the post.
	 *
	 * @return bool Whether any references were removed.
	 */
	private function remove_invalid_note_ids( array &$blocks, array $valid_note_ids ) {
		$changed = false;

		foreach ( $blocks as &$block ) {
			if ( isset( $block['attrs']['metadata']['noteId'] ) ) {
				$note_ids = (array) $block['attrs']['metadata']['noteId'];
				$filtered = \array_values(
					\array_filter(
						$note_ids,
						static function ( $note_id ) use ( $valid_note_ids ) {
							return \in_array( (int) $note_id, $valid_note_ids, true );
						},
					),
				);

				if ( \count( $filtered ) !== \count( $note_ids ) ) {
					$changed = true;
					if ( empty( $filtered ) ) {
						unset( $block['attrs']['metadata']['noteId'] );
					}
					elseif ( \is_array( $block['attrs']['metadata']['noteId'] ) ) {
						$block['attrs']['metadata']['noteId'] = $filtered;
					}
					else {
						$block['attrs']['metadata']['noteId'] = $filtered[0];
					}
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && $this->remove_invalid_note_ids( $block['innerBlocks'], $valid_note_ids ) ) {
				$changed = true;
			}
		}
		unset( $block );

		return $changed;
	}
}
