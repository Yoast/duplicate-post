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

	handleRewritingPost() {
		if ( ! duplicatePostRewriteRepost.rewriting ) {
			return;
		}

		const hasActiveMetaBoxes = select( 'core/edit-post' ).hasMetaBoxes();
		let wasSavingPost        = false;
		let wasSavingMetaboxes   = false;
		let wasAutoSavingPost    = false;

		subscribe( () => {
			const isSavingPost      = select( 'core/editor' ).isSavingPost();
			const isAutosavingPost  = select( 'core/editor' ).isAutosavingPost();
			const isSavingMetaBoxes = select( 'core/edit-post' ).isSavingMetaBoxes();

			if ( hasActiveMetaBoxes && ! isSavingMetaBoxes && wasSavingMetaboxes ) {
				window.location.href = duplicatePostRewriteRepost.originalEditURL;
			}
			if ( ! hasActiveMetaBoxes && ! isSavingPost && wasSavingPost && ! wasAutoSavingPost ) {
				window.location.href = duplicatePostRewriteRepost.originalEditURL;
			}

			wasSavingPost      = isSavingPost;
			wasSavingMetaboxes = isSavingMetaBoxes;
			wasAutoSavingPost  = isAutosavingPost;
		} );
	}

	renderNotices() {
		if ( parseInt( duplicatePostRewriteRepost.rewriting ) ) {
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

		if ( parseInt( duplicatePostRewriteRepost.republished ) ) {
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
instance.handleRewritingPost();

registerPlugin( 'duplicate-post', {
	render: instance.render
} );
