/* global yoastRewriteRepost */

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
		{ yoastRewriteRepost.permalink !== "" &&
		  <a href={ yoastRewriteRepost.permalink }>{ __( "Rewrite & Republish", "duplicate-post" ) }</a> }
	</PluginPostStatusInfo>
);

registerPlugin( "yoast-duplicate-post", {
	render
} );
