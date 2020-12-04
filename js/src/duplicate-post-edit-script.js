/* global duplicatePostRewriteRepost */
/* global wp */

import { registerPlugin } from "@wordpress/plugins";
import { PluginPostStatusInfo } from "@wordpress/edit-post";
import { __ } from "@wordpress/i18n";
import { select, subscribe, dispatch } from "@wordpress/data";
import apiFetch from '@wordpress/api-fetch';

class DuplicatePost {
	constructor() {
		this.renderNotices();
	}

	/**
	 * Handles the redirect from the copy to the original.
	 *
	 * @returns {void}
	 */
	handleRedirect() {
		if ( ! parseInt( duplicatePostRewriteRepost.rewriting, 10 ) ) {
			return;
		}

		let wasSavingPost      = false;
		let wasSavingMetaboxes = false;
		let wasAutoSavingPost  = false;

		/**
		 * Determines when the redirect needs to happen.
		 *
		 * @returns {void}
		 */
		subscribe( () => {
			const isSavingPost       = select( 'core/editor' ).isSavingPost();
			const isAutosavingPost   = select( 'core/editor' ).isAutosavingPost();
			const hasActiveMetaBoxes = select( 'core/edit-post' ).hasMetaBoxes();
			const isSavingMetaBoxes  = select( 'core/edit-post' ).isSavingMetaBoxes();

			// When there are custom meta boxes, redirect after they're saved.
			if ( hasActiveMetaBoxes && ! isSavingMetaBoxes && wasSavingMetaboxes ) {
				window.location.href = duplicatePostRewriteRepost.originalEditURL;
			}

			// When there are no custom meta boxes, redirect after the post is saved.
			if ( ! hasActiveMetaBoxes && ! isSavingPost && wasSavingPost && ! wasAutoSavingPost ) {
				window.location.href = duplicatePostRewriteRepost.originalEditURL;
			}

			wasSavingPost      = isSavingPost;
			wasSavingMetaboxes = isSavingMetaBoxes;
			wasAutoSavingPost  = isAutosavingPost;
		} );
	}

	/**
	 * Renders the notices in the block editor.
	 *
	 * @returns {void}
	 */
	renderNotices() {
		if ( parseInt( duplicatePostRewriteRepost.rewriting, 10 ) ) {
			dispatch( 'core/notices' ).createNotice(
				'warning',
				__(
					'You can now start rewriting your post in this duplicate of the original post. If you click "Republish", this rewritten post will replace the original post.',
					'duplicate-post'
				),
				{
					isDismissible: true, // Whether the user can dismiss the notice.
				}
			);
		}

		if ( parseInt( duplicatePostRewriteRepost.republished, 10 ) ) {
			dispatch( 'core/notices' ).createNotice(
				'warning',
				duplicatePostRewriteRepost.republishedText,
				{
					isDismissible: true, // Whether the user can dismiss the notice.
				}
			);
		}
	}

	/**
	 * Renders the Rewrite & Republish link in the PluginPostStatusInfo component.
	 *
	 * @returns {JSX.Element} The rendered link.
	 */
	render() {
		return (
			<PluginPostStatusInfo>
				{ duplicatePostRewriteRepost.permalink !== '' &&
				  <a href={ duplicatePostRewriteRepost.permalink }>{ __( 'Rewrite & Republish', 'duplicate-post' ) }</a> }
			</PluginPostStatusInfo>
		);
	}
}

const instance = new DuplicatePost();
instance.handleRedirect();

registerPlugin( 'duplicate-post', {
	render: instance.render
} );
