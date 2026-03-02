/* global require, module */
const path = require( "path" );

const jsDistPath = path.resolve( "js", "dist" );
const jsSrcPath = path.resolve( "js", "src" );

// Entries for webpack to make bundles from.
const entry = {
	"duplicate-post-edit": "./duplicate-post-edit-script.js",
	"duplicate-post-strings": "./duplicate-post-strings.js",
	"duplicate-post-quick-edit": "./duplicate-post-quick-edit-script.js",
	"duplicate-post-options": "./duplicate-post-options.js",
	"duplicate-post-elementor": "./duplicate-post-elementor.js",
};

module.exports = {
	entry,
	jsDist: jsDistPath,
	jsSrc: jsSrcPath,
};
