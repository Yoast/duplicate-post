/* global duplicatePostStrings */

import { createInterpolateElement } from "@wordpress/element";
import { Button } from "@wordpress/components";
import { __, setLocaleData } from "@wordpress/i18n";
import { dispatch, subscribe } from "@wordpress/data";
import { redirectOnSaveCompletion } from "./duplicate-post-functions";

const saveAndCompare = () => {
	dispatch( 'core/editor' ).savePost();

	let wasSavingPost      = false;
	let wasSavingMetaboxes = false;
	let wasAutoSavingPost  = false;

	/**
	 * Determines when the redirect needs to happen.
	 *
	 * @returns {void}
	 */
	subscribe( () => {
		const completed = redirectOnSaveCompletion( duplicatePostStrings.checkLink, { wasSavingPost, wasSavingMetaboxes, wasAutoSavingPost } );

		wasSavingPost      = completed.isSavingPost;
		wasSavingMetaboxes = completed.isSavingMetaBoxes;
		wasAutoSavingPost  = completed.isAutosavingPost;
	} );
}

const republishStrings = {
	'Publish'  : __( 'Republish', 'duplicate-post' ),
	'Publish:' : __( 'Republish:', 'duplicate-post' ),

	'Are you ready to publish?'	: __( 'Are you ready to republish your post?', 'duplicate-post' ),
	'Double-check your settings before publishing.':
		createInterpolateElement(
			__( 'After republishing your changes will be merged into the original post and you\'ll be redirected there.<br /><br />Do you want to compare your changes with the original version before merging?<br /><br /><button>Save changes and compare</button>',
				'duplicate-post' ),
			{
				button: <Button isSecondary onClick={ saveAndCompare } />,
				br: <br />
			}
		),

	'Schedule'  : __( 'Schedule republish', 'duplicate-post' ),
	'Schedule…' : __( 'Schedule republish…', 'duplicate-post' ),
	'post action/button label\u0004Schedule' : __( 'Schedule republish', 'duplicate-post' ),

	'Are you ready to schedule?' : __( 'Are you ready to schedule the republishing of your post?', 'duplicate-post' ),
	'Your work will be published at the specified date and time.':
		createInterpolateElement(
			__( 'You\'re about to replace the original with this rewritten post at the specified date and time.<br /><br />Do you want to compare your changes with the original version before merging?<br /><br /><button>Save changes and compare</button>',
				'duplicate-post' ),
			{
				button: <Button isSecondary onClick={ saveAndCompare } />,
				br: <br />
			}
		),
	'is now scheduled. It will go live on':
		__( ', the rewritten post, is now scheduled to replace the original post. It will be published on',
		'duplicate-post' ),
};

for ( const original in republishStrings ) {
	setLocaleData( {
		[ original ]: [
			republishStrings[ original ],
			'duplicate-post'
		]
	} );
}
