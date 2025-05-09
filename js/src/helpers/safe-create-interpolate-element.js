import { createInterpolateElement } from "@wordpress/element";

/**
 * Wrapper function for `createInterpolateElement` to catch errors.
 *
 * @param {string} interpolatedString The interpolated string.
 * @param {object} conversionMap The conversion map object.
 * @returns {string} The interpolated string.
 */
export const safeCreateInterpolateElement = ( interpolatedString, conversionMap ) => {
	try {
		return createInterpolateElement( interpolatedString, conversionMap );
	} catch ( error ) {
		console.error( "Error in translation for:", interpolatedString, error );
		return interpolatedString;
	}
};
