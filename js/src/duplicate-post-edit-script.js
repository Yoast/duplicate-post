/* global wp, duplicatePost */

import { registerPlugin } from "@wordpress/plugins";
import { PluginPostStatusInfo } from "@wordpress/edit-post";
import { Fragment } from "@wordpress/element"
import { Button } from '@wordpress/components';
import { __ } from "@wordpress/i18n";
import { select, subscribe, dispatch } from "@wordpress/data";

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
		if ( ! parseInt( duplicatePost.rewriting, 10 ) ) {
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
				window.location.href = duplicatePost.originalEditURL;
			}

			// When there are no custom meta boxes, redirect after the post is saved.
			if ( ! hasActiveMetaBoxes && ! isSavingPost && wasSavingPost && ! wasAutoSavingPost ) {
				window.location.href = duplicatePost.originalEditURL;
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
		if ( parseInt( duplicatePost.rewriting, 10 ) ) {
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

		if ( parseInt( duplicatePost.republished, 10 ) ) {
			dispatch( 'core/notices' ).createNotice(
				'success',
				duplicatePost.republishedText,
				{
					isDismissible: true, // Whether the user can dismiss the notice.
				}
			);
		}
	}

	/**
	 * Renders the links in the PluginPostStatusInfo component.
	 *
	 * @returns {JSX.Element} The rendered links.
	 */
	render() {
		return (
			<Fragment>
				{ duplicatePost.new_draft_link !== '' &&
					<PluginPostStatusInfo>
						<Button
							isTertiary={ true }
							className="dp-editor-post-copy-to-draft"
							href={ duplicatePost.new_draft_link }
						>
							{ __( 'Copy to a new draft', 'duplicate-post' ) }
						</Button>
					</PluginPostStatusInfo>
				}
				{ duplicatePost.rewrite_and_republish_link !== '' &&
					<PluginPostStatusInfo>
						<Button
							isTertiary={ true }
							className="dp-editor-post-rewrite-republish"
							href={ duplicatePost.rewrite_and_republish_link }
						>
							{ __( 'Rewrite & Republish', 'duplicate-post' ) }
						</Button>
					</PluginPostStatusInfo>
				}
			</Fragment>
		);
	}
}

const instance = new DuplicatePost();
instance.handleRedirect();

registerPlugin( 'duplicate-post', {
	render: instance.render
} );
