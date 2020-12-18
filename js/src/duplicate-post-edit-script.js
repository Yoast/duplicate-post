/* global duplicatePost, duplicatePostNotices */

import { registerPlugin } from "@wordpress/plugins";
import { PluginPostStatusInfo } from "@wordpress/edit-post";
import { Fragment } from "@wordpress/element";
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
			if ( ! this.isSafeRedirectURL( duplicatePost.originalEditURL ) ) {
				return;
			}

			const isSavingPost       = select( 'core/editor' ).isSavingPost();
			const isAutosavingPost   = select( 'core/editor' ).isAutosavingPost();
			const hasActiveMetaBoxes = select( 'core/edit-post' ).hasMetaBoxes();
			const isSavingMetaBoxes  = select( 'core/edit-post' ).isSavingMetaBoxes();

			if ( ! this.is_copy_allowed_to_be_republished() ) {
				return;
			}

			// When there are custom meta boxes, redirect after they're saved.
			if ( hasActiveMetaBoxes && ! isSavingMetaBoxes && wasSavingMetaboxes ) {
				window.location.assign( duplicatePost.originalEditURL );
			}

			// When there are no custom meta boxes, redirect after the post is saved.
			if ( ! hasActiveMetaBoxes && ! isSavingPost && wasSavingPost && ! wasAutoSavingPost ) {
				window.location.assign( duplicatePost.originalEditURL );
			}

			wasSavingPost      = isSavingPost;
			wasSavingMetaboxes = isSavingMetaBoxes;
			wasAutoSavingPost  = isAutosavingPost;
		} );
	}

	/**
	 * Checks whether the URL for the redirect from the copy to the original matches the expected format.
	 *
	 * Allows only URLs with a http(s) protocol, a pathname matching the admin
	 * post.php page and a parameter string with the expected parameters.
	 *
	 * @returns {bool} Whether the redirect URL matches the expected format.
	 */
	isSafeRedirectURL( url ) {
		const parser = document.createElement( 'a' );
		parser.href  = url;

		if (
			/^https?:$/.test( parser.protocol ) &&
			/\/wp-admin\/post\.php$/.test( parser.pathname ) &&
			/\?action=edit&post=[0-9]+&dprepublished=1&dpcopy=[0-9]+&dpnonce=[a-z0-9]+/i.test( parser.search )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Determines whether a Rewrite & Republish copy can be republished.
	 *
	 * @return bool Whether the Rewrite & Republish copy can be republished.
	 */
	is_copy_allowed_to_be_republished() {
		const currentPostStatus = select( 'core/editor' ).getEditedPostAttribute( 'status' );

		if ( currentPostStatus === 'dp-rewrite-republish' || currentPostStatus === 'private' ) {
			return true;
		}

		return false;
	}

	/**
	 * Renders the notices in the block editor.
	 *
	 * @returns {void}
	 */
	renderNotices() {
		if ( ! duplicatePostNotices || ! ( duplicatePostNotices instanceof Object ) ) {
			return;
		}

		for ( const [ key, notice ] of Object.entries( duplicatePostNotices ) ){
			let noticeObj = JSON.parse( notice );
			if ( noticeObj.status && noticeObj.text ) {
				dispatch( 'core/notices' ).createNotice(
					noticeObj.status,
					noticeObj.text,
					{
						isDismissible: noticeObj.isDismissible || true,
					}
				);
			}
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
