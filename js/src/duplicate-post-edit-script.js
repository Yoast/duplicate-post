import { registerPlugin } from "@wordpress/plugins";
import { PluginPostStatusInfo } from "@wordpress/edit-post";
import { __ } from "@wordpress/i18n";

/**
 * Renders the plugin.
 *
 * @returns {JSX.Element} The rendered plugin.
 */
const render = () => (
	<PluginPostStatusInfo>
		<a href="#">{ __( "Rewrite & Republish", "duplicate-post" ) }</a>
	</PluginPostStatusInfo>
);

registerPlugin( "yoast-duplicate-post", {
	render
} );
