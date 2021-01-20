/* global $e, duplicatePost, elementor */

/**
 * Hooks into Elementor on initialization.
 *
 * @returns {void}
 */
function duplicatePostOnElementorInitialize() {

	/**
	 * Class that defines the redirect action to execute after republishing.
	 */
	class RedirectAfterRepublish extends $e.modules.hookUI.After {

		/**
		 * Gets the command to run on.
		 *
		 * @returns {string} The command.
		 */
		getCommand() {
			return "document/save/save";
		}

		/**
		 * Gets the conditions on which to run on.
		 *
		 * @param {Object} args The arguments to use.
		 *
		 * @returns {boolean} Whether the post status is that of a published post.
		 */
		getConditions( args ) {
			const { status } = args;

			return status === "publish";
		}

		/**
		 * Gets the ID of the action.
		 *
		 * @returns {string} The ID.
		 */
		getId() {
			return "redirect-after-republish";
		}

		/**
		 * Applies the redirect if the condtions are met.
		 *
		 * @param {Object} args The arguments to use.
		 *
		 * @returns {void}
		 */
		apply( args ) {
			if ( args.status === "publish" && duplicatePost.originalEditURL ) {
				window.location.assign( duplicatePost.originalEditURL );
			}
		}
	}

	$e.hooks.registerUIAfter( new RedirectAfterRepublish() );
}

/**
 * Removes the Save as Template option for Rewrite and Republish copy.
 *
 * @returns {void}
 */
function duplicatePostRemoveSaveTemplate() {
	if ( duplicatePost.rewriting === "0" ) {
		return;
	}

	elementor
		.getPanelView()
		.footer
		.currentView
		.removeSubMenuItem( 'saver-options', {
			name: 'save-template',
		} );
}

// Wait on `window.elementor`.
jQuery( window ).on( "elementor:init", () => {
	// Wait on Elementor app to have started.
	window.elementor.on( "panel:init", () => {
		duplicatePostOnElementorInitialize();
		duplicatePostRemoveSaveTemplate();
	} );
} );
