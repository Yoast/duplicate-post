/* global $e */

function duplicatePostOnElementorInitialize() {

    class RedirectAfterRepublish extends $e.modules.hookUI.After {
        getCommand() {
            return 'document/save/save';
        }

        getConditions(args) {
            const { status } = args;
            return 'publish' === status;
        }

        getId() {
            return 'redirect-after-republish';
        }

        apply(args) {
            if ('publish' === args.status && duplicatePost.originalEditURL) {
                window.location.assign(duplicatePost.originalEditURL);
            }
        }
    }

    $e.hooks.registerUIAfter( new RedirectAfterRepublish() );
}

// Wait on `window.elementor`.
jQuery( window ).on( "elementor:init", () => {
    // Wait on Elementor app to have started.
    window.elementor.on( "panel:init", () => {
        duplicatePostOnElementorInitialize();
    } );
} );
