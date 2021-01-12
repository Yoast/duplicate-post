let tablist;
let tabs;
let panels;

// For easy reference
const keys = {
	end: 35,
	home: 36,
	left: 37,
	up: 38,
	right: 39,
	down: 40,
	delete: 46
};

// Add or substract depending on key pressed
const direction = {
	37: - 1,
	38: - 1,
	39: 1,
	40: 1
};


function generateArrays() {
	tabs = document.querySelectorAll( "#duplicate_post_settings_form [role=\"tab\"]" );
	panels = document.querySelectorAll( "#duplicate_post_settings_form [role=\"tabpanel\"]" );
}

function addListeners( index ) {
	tabs[index].addEventListener( "click", function ( event ) {
		const tab = event.target;
		activateTab( tab, false );
	} );
	tabs[index].addEventListener( "keydown", function ( event ) {
		const key = event.keyCode;

		switch ( key ) {
			case keys.end:
				event.preventDefault();
				// Activate last tab
				activateTab( tabs[tabs.length - 1] );
				break;
			case keys.home:
				event.preventDefault();
				// Activate first tab
				activateTab( tabs[0] );
				break;
			default:
				break;
		}
	} );
	tabs[index].addEventListener( "keyup", function ( event ) {
		const key = event.keyCode;

		switch ( key ) {
			case keys.left:
			case keys.right:
				switchTabOnArrowPress( event );
				break;
			default:
				break;
		}
	} );

	// Build an array with all tabs (<button>s) in it
	tabs[index].index = index;
}


// Either focus the next, previous, first, or last tab
// depending on key pressed
function switchTabOnArrowPress( event ) {
	const pressed = event.keyCode;

	for ( let x = 0; x < tabs.length; x ++ ) {
		tabs[x].addEventListener( "focus", focusEventHandler );
	}

	if ( direction[pressed] ) {
		const target = event.target;
		if ( target.index !== undefined ) {
			if ( tabs[target.index + direction[pressed]] ) {
				tabs[target.index + direction[pressed]].focus();
			}
			else if ( pressed === keys.left || pressed === keys.up ) {
				focusLastTab();
			}
			else if ( pressed === keys.right || pressed === keys.down ) {
				focusFirstTab();
			}
		}
	}
}

// Activates any given tab panel
function activateTab( tab, setFocus ) {
	setFocus = setFocus || true;
	// Deactivate all other tabs
	deactivateTabs();

	// Remove tabindex attribute
	tab.removeAttribute( "tabindex" );

	// Set the tab as selected
	tab.setAttribute( "aria-selected", "true" );

	tab.classList.add( "nav-tab-active" );

	// Get the value of aria-controls (which is an ID)
	const controls = tab.getAttribute( "aria-controls" );

	// Remove hidden attribute from tab panel to make it visible
	document.getElementById( controls )
	        .removeAttribute( "hidden" );

	// Set focus when required
	if ( setFocus ) {
		tab.focus();
	}
}

// Deactivate all tabs and tab panels
function deactivateTabs() {
	for ( let t = 0; t < tabs.length; t ++ ) {
		tabs[t].setAttribute( "tabindex", "-1" );
		tabs[t].setAttribute( "aria-selected", "false" );
		tabs[t].classList.remove( "nav-tab-active" );
		tabs[t].removeEventListener( "focus", focusEventHandler );
	}

	for ( let p = 0; p < panels.length; p ++ ) {
		panels[p].setAttribute( "hidden", "hidden" );
	}
}

// Make a guess
function focusFirstTab() {
	tabs[0].focus();
}

// Make a guess
function focusLastTab() {
	tabs[tabs.length - 1].focus();
}

//
function focusEventHandler( event ) {
	const target = event.target;

	checkTabFocus( target );
}

// Only activate tab on focus if it still has focus after the delay
function checkTabFocus( target ) {
	const focused = document.activeElement;

	if ( target === focused ) {
		activateTab( target, false );
	}
}

document.addEventListener( "DOMContentLoaded", function () {
	tablist = document.querySelectorAll( "#duplicate_post_settings_form [role=\"tablist\"]" )[0];

	generateArrays();

	// Bind listeners
	for ( let i = 0; i < tabs.length; ++ i ) {
		addListeners( i );
	}
} );

jQuery( function () {
	jQuery( ".taxonomy_private" )
		.hide();

	jQuery( ".toggle-private-taxonomies" )
		.on( "click", function () {
			const buttonElement = jQuery( this );

			jQuery( ".taxonomy_private" )
				.toggle( 300, function () {
					buttonElement.attr( "aria-expanded", jQuery( this )
						.is( ":visible" ) );
				} );
		} );
} );
