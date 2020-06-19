/**
 * Injects column data about original post into the Quick Edit.
 *
 * @package Duplicate Post
 * @since 3.2.4
 */

(function(jQuery) {
	var $wp_inline_edit = inlineEditPost.edit;
	inlineEditPost.edit = function( id ) {
		$wp_inline_edit.apply( this, arguments );
		var $post_id = 0;
		if ( typeof( id ) == 'object' ) {
			$post_id = parseInt( this.getId( id ) );
		}
		if ( $post_id > 0 ) {
			var $edit_row = jQuery( '#edit-' + $post_id );
			var $post_row = jQuery( '#post-' + $post_id );

			var has_original = ( jQuery( '.duplicate_post_original_item span[data-no_original]', $post_row ).length === 0 );
			var original = jQuery( '.duplicate_post_original_item', $post_row ).html();

			if ( has_original ) {
				jQuery( '.duplicate_post_original_item_title_span', $edit_row ).html( original );
				jQuery( '#duplicate_post_quick_edit_fieldset', $edit_row ).show();
			} else {
				jQuery( '#duplicate_post_quick_edit_fieldset', $edit_row ).hide();
				jQuery( '.duplicate_post_original_item_title_span', $edit_row ).html( '' );
			}
		}
	};

} )( jQuery );
