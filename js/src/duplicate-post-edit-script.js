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
		const isSavingPost = wp.data.select( 'core/editor' ).isSavingPost();
		const isAutosavingPost = wp.data.select( 'core/editor' ).isAutosavingPost();
		const didPostSaveRequestSucceed = wp.data.select( 'core/editor' ).didPostSaveRequestSucceed();
		const currentPostStatus = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );

		// These can be used to determine whether active metaboxes are saving.
		const hasActiveMetaBoxes = wp.data.select( 'core/edit-post' ).hasMetaBoxes();
		const isSavingMetaBoxes = wp.data.select( 'core/edit-post' ).isSavingMetaBoxes();

//		console.log( { isSavingPost, currentPostStatus, hasActiveMetaBoxes, isSavingMetaBoxes } );
		if (
			! isSavingPost &&
			! isAutosavingPost &&
			didPostSaveRequestSucceed &&
			! isSavingMetaBoxes &&
			currentPostStatus === "rewrite_republish" &&
			duplicatePostRewriteRepost.originalEditURL
		) {
			console.log( 'redirecting now' );
//			window.location.href = duplicatePostRewriteRepost.originalEditURL;
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

function createRedirectMiddleware() {
	return ( options, next ) => {
		// Don't run the middleware on GET requests, because it might interfere with the fetch-all middleware.
		if ( typeof options.method === "undefined" || options.method === "GET" ) {
			return next( options );
		}

		const nextOptions = {
			...options,
			parse: false,
		};

		return next( nextOptions ).then( ( response ) => {
			const redirectHeader = response.headers.get( "X-Yoast-Meta-Stored" );

			// This gets called one request too early.
			if ( redirectHeader ) {
				window.location.href = duplicatePostRewriteRepost.originalEditURL;
			}

			return response;
		} );
	};
}

apiFetch.use( createRedirectMiddleware() );

const instance = new DuplicatePost();
wp.data.subscribe( instance.handleRewritingPost );

registerPlugin( 'duplicate-post', {
	render: instance.render
} );
