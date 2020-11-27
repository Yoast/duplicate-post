/* global duplicatePostRewriteRepost */
/* global wp */

import { registerPlugin } from "@wordpress/plugins";
import { PluginPostStatusInfo } from "@wordpress/edit-post";
import { __ } from "@wordpress/i18n";
import { select, subscribe, dispatch } from "@wordpress/data";

class DuplicatePost {
	constructor() {
		this.renderNotices();
	}

	handleRewritingPost() {
		const {
			isSavingPost,
			isAutosavingPost,
			didPostSaveRequestSucceed,
			getEditedPostAttribute
		} = select( 'core/editor' );

		const currentPostStatus = getEditedPostAttribute( 'status' );

		if ( ! isSavingPost && ! isAutosavingPost && didPostSaveRequestSucceed && currentPostStatus === "rewrite_republish" ) {
			console.log( "saved", currentPostStatus );
//				window.location.href = {insert URL here};
		}
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
wp.data.subscribe( instance.handleRewritingPost );

registerPlugin( 'duplicate-post', {
	render: instance.render
} );
