/* global duplicatePost */

import { registerPlugin } from "@wordpress/plugins";
import { PluginPostStatusInfo } from "@wordpress/edit-post";
import { Fragment } from "@wordpress/element"
import { Button } from '@wordpress/components';
import { __ } from "@wordpress/i18n";

/**
 * Renders the links in the PluginPostStatusInfo component.
 *
 * @returns {JSX.Element} The rendered links.
 */
const render = () => (
	<Fragment>
		{ duplicatePost.new_draft_link !== '' &&
			<PluginPostStatusInfo>
				<Button
					isTertiary={ true }
					href={ duplicatePost.new_draft_link }
				>
					{ __( 'Copy to a new draft', 'duplicate-post' ) }
				</Button>
			</PluginPostStatusInfo>
		}
		{duplicatePost.rewrite_and_republish_link !== '' &&
			<PluginPostStatusInfo>
				<Button
					isTertiary={ true }
					href={ duplicatePost.rewrite_and_republish_link }
				>
					{ __( 'Rewrite & Republish', 'duplicate-post' ) }
				</Button>
			</PluginPostStatusInfo>
		}
	</Fragment>
);

registerPlugin( 'duplicate-post', {
	render
} );

if ( parseInt( duplicatePost.rewriting ) ) {
	( function( wp ) {
		wp.data.dispatch( 'core/notices' ).createNotice(
			'warning',
			__(
				'You can now start rewriting your post in this duplicate of the original post. If you click "Republish", this rewritten post will replace the original post.',
				'duplicate-post'
			),
			{
				isDismissible: true, // Whether the user can dismiss the notice.
			}
		);
	} )( window.wp );
}
