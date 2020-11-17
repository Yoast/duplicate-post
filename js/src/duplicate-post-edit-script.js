/* global duplicatePostRewriteRepost */

import { registerPlugin } from "@wordpress/plugins";
import { PluginPostStatusInfo } from "@wordpress/edit-post";
import { __ } from "@wordpress/i18n";

/**
 * Renders the Rewrite & Republish link in the PluginPostStatusInfo component.
 *
 * @returns {JSX.Element} The rendered link.
 */
const render = () => (
	<PluginPostStatusInfo>
		{ duplicatePostRewriteRepost.permalink !== '' &&
		  <a href={ duplicatePostRewriteRepost.permalink }>{ __( 'Rewrite & Republish', 'duplicate-post' ) }</a> }
	</PluginPostStatusInfo>
);

registerPlugin( 'duplicate-post', {
	render
} );

if ( parseInt( duplicatePostRewriteRepost.rewriting) ) {
	(function (wp) {
		wp.data.dispatch('core/notices').createNotice(
			'warning',
			__(
				'You can now start rewriting your post in this duplicate of the original post. If you click "Republish", this rewritten post will replace the original post.',
				'duplicate-post'
			),
			{
				isDismissible: true, // Whether the user can dismiss the notice.
			}
		);
	})(window.wp);
}
