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
		{ duplicatePostRewriteRepost.permalink !== "" &&
		  <a href={ duplicatePostRewriteRepost.permalink }>{ __( "Rewrite & Republish", "duplicate-post" ) }</a> }
	</PluginPostStatusInfo>
);

registerPlugin( "duplicate-post", {
	render
} );
