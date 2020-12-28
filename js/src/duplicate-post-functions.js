import { select } from "@wordpress/data";

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
		window.location.assign( url );
	}

	// When there are no custom meta boxes, redirect after the post is saved.
	if ( ! hasActiveMetaBoxes && ! isSavingPost && editorState.wasSavingPost && ! editorState.wasAutoSavingPost ) {
		window.location.assign( url );
	}

	return { isSavingPost, isSavingMetaBoxes, isAutosavingPost };
};
