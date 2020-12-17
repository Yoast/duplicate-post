const path = require( "path" );

// See https://github.com/sindresorhus/grunt-shell
module.exports = function( grunt ) {
	/**
	 * Will throw an error if there are uncommitted changes.
	 *
	 * @param {*}        error     A potential error in calling in the git status --porcelain command.
	 * @param {*}        stdout    The response if no errors.
	 * @param {*}        stderr    A stderr.
	 * @param {Function} callback  The callback function.
	 *
	 * @returns {void}
	 */
	function throwUncommittedChangesError( error, stdout, stderr, callback ) {
		if ( stdout ) {
			throw "You have uncommitted changes. Commit, stash or reset the above files.";
		} else {
			grunt.log.ok( "You have no uncommitted changes. Continuing..." );
		}
		callback();
	}

	// Temporarily disable require-jsdoc due to the structure of the code below.
	/* eslint-disable require-jsdoc */
	return {

		"composer-install-production": {
			command: "composer install --prefer-dist --optimize-autoloader --no-dev --no-scripts",
		},

		"composer-install": {
			command: "composer install",
		},

		"composer-reset-config": {
			command: "git checkout composer.json",
			options: {
				failOnError: false,
			},
		},

		"composer-reset-lock": {
			command: "git checkout composer.lock",
			options: {
				failOnError: false,
			},
		},

		"production-prefix-dependencies": {
			command: "composer install",
		},
	};
	/* eslint-enable require-jsdoc */
};
