import { createInterpolateElement } from "@wordpress/element";
import { __, setLocaleData } from "@wordpress/i18n";

const republishStrings = {
	'Publish'  : __( 'Republish', 'duplicate-post' ),
	'Publish:' : __( 'Republish:', 'duplicate-post' ),

	'Are you ready to publish?':
		__( 'Are you ready to republish your post?', 'duplicate-post' ),
	'Double-check your settings before publishing.':
		createInterpolateElement(
			__( 'After republishing your changes will be merged into the original post and you\'ll be redirected there.<br /><br /><a>Do you want to double-check your changes before merging?</a>',
				'duplicate-post' ),
			{ a: <a href="#" />,
				br: <br /> }
		),

	'Schedule'  : __( 'Schedule republish', 'duplicate-post' ),
	'Schedule…' : __( 'Schedule republish…', 'duplicate-post' ),
	'post action/button label\u0004Schedule' : __( 'Schedule republish', 'duplicate-post' ),

	'Are you ready to schedule?':
		__( 'Are you ready to schedule the republishing of your post?', 'duplicate-post' ),
	'Your work will be published at the specified date and time.':
		createInterpolateElement(
			__( 'You\'re about to replace the original with this rewritten post at the specified date and time.<br /><br /><a>Do you want to double-check your changes before merging?</a>',
				'duplicate-post' ),
			{
				a: <a href="#" />,
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
