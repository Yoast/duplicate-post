import { dispatch, select } from "@wordpress/data";

/**
 * This redirects without showing the warning that occurs due to a Gutenberg bug.
 *
 * Edits made to the post on the PHP side are not correctly recognized and thus the warning for unsaved changes is shown.
 * By updating the post status ourselves on the JS side as well we avoid this.
 *
 * @param {string} url The url to redirect to.
 *
 * @returns {void}
 */
const redirectWithoutWarning = ( url ) => {
	const currentPostStatus = select( 'core/editor' ).getCurrentPostAttribute( 'status' );
	const editedPostStatus  = select( 'core/editor' ).getEditedPostAttribute( 'status' );

	if ( currentPostStatus === 'dp-rewrite-republish' && editedPostStatus === 'publish' ) {
		dispatch( 'core/editor' ).editPost( { status: currentPostStatus } );
	}

	window.location.assign( url );
}

/**
 * Redirects to url when saving in the block editor has completed.
 *
 * @param {string} url         The url to redirect to.
 * @param {Object} editorState The current editor state regarding saving the post, metaboxes and autosaving.
 *
 * @returns {Object} The updated editor state.
 */
export const redirectOnSaveCompletion = ( url, editorState ) => {
	const isSavingPost       = select( 'core/editor' ).isSavingPost();
	const isAutosavingPost   = select( 'core/editor' ).isAutosavingPost();
	const hasActiveMetaBoxes = select( 'core/edit-post' ).hasMetaBoxes();
	const isSavingMetaBoxes  = select( 'core/edit-post' ).isSavingMetaBoxes();

	// When there are custom meta boxes, redirect after they're saved.
	if ( hasActiveMetaBoxes && ! isSavingMetaBoxes && editorState.wasSavingMetaboxes ) {
		redirectWithoutWarning( url );
	}

	// When there are no custom meta boxes, redirect after the post is saved.
	if ( ! hasActiveMetaBoxes && ! isSavingPost && editorState.wasSavingPost && ! editorState.wasAutoSavingPost ) {
		redirectWithoutWarning( url );
	}

	return { isSavingPost, isSavingMetaBoxes, isAutosavingPost };
};
