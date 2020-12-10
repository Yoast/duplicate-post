/* global duplicatePostLinks, duplicatePostNotices */

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
		{ duplicatePostLinks.new_draft_link !== '' &&
			<PluginPostStatusInfo>
				<Button
					isTertiary={ true }
					className="dp-editor-post-copy-to-draft"
					href={ duplicatePostLinks.new_draft_link }
				>
					{ __( 'Copy to a new draft', 'duplicate-post' ) }
				</Button>
			</PluginPostStatusInfo>
		}
		{ duplicatePostLinks.rewrite_and_republish_link !== '' &&
			<PluginPostStatusInfo>
				<Button
					isTertiary={ true }
					className="dp-editor-post-rewrite-republish"
					href={ duplicatePostLinks.rewrite_and_republish_link }
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

( function( wp ) {
	if ( ! duplicatePostNotices || ! ( duplicatePostNotices instanceof Object ) ) {
		return;
	}
	for ( const [ key, notice ] of Object.entries( duplicatePostNotices ) ){
		let noticeObj = JSON.parse( notice );
		if ( noticeObj.status && noticeObj.text ) {
			wp.data.dispatch('core/notices').createNotice(
				noticeObj.status,
				noticeObj.text,
				{
					isDismissible: noticeObj.isDismissible || true,
				}
			);
		}
	}
} )( window.wp );
