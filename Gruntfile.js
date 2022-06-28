/* global require, process */
const { flattenVersionForFile } = require( './config/webpack/paths' );
const path = require( "path" );

module.exports = function( grunt ) {
	'use strict';

	const pkg = grunt.file.readJSON( 'package.json' );
	const pluginVersion = pkg.yoast.pluginVersion;

	/* Used to switch between development and release builds.
	Switches based on the grunt command (which is the third 'argv', after node and grunt,  so index 2).*/
	const developmentBuild = ! [ "create-rc", "release", "release:js", "artifact", "deploy:trunk", "deploy:master" ].includes( process.argv[ 2 ] );

	const project = {
		developmentBuild,
		pluginVersion,
		pluginVersionSlug: flattenVersionForFile( pluginVersion ),
		pluginSlug: "duplicate-post",
		pluginMainFile: "duplicate-post.php",
		paths: {
			get config() {
				return this.grunt + 'task-config/';
			},
			grunt: 'config/grunt/',
			images: 'images/',
			js: 'js/',
			svnCheckoutDir: '.wordpress-svn',
			assets: 'svn-assets',
			vendor: 'vendor/',
		},
		files: {
			images: [
				'images/*'
			],
			js: [
				'js/src/*.js',
				'!js/dist/*.min.js'
			],
			artifact: "artifact",
			get config() {
				return project.paths.config + '*.js';
			},
			grunt: 'Gruntfile.js'
		},
		versionFiles: [
			"package.json",
			"duplicate-post.php",
		],
		pkg,
	};

	// Load Grunt configurations and tasks
	require( 'load-grunt-config' )( grunt, {
		configPath: path.join( process.cwd(), "node_modules/@yoast/grunt-plugin-tasks/config/" ),
		overridePath: path.join( process.cwd(), project.paths.config ),
		data: project,
		jitGrunt: {
			staticMappings: {
				gittag: "grunt-git",
				gitfetch: "grunt-git",
				gitadd: "grunt-git",
				gitstatus: "grunt-git",
				gitcommit: "grunt-git",
				gitcheckout: "grunt-git",
				gitpull: "grunt-git",
				gitpush: "grunt-git",
				"set-version": "@yoast/grunt-plugin-tasks",
				"update-version": "@yoast/grunt-plugin-tasks",
				"update-changelog-to-latest": "@yoast/grunt-plugin-tasks",
			},
			customTasksDir: 'config/grunt/custom-tasks',
		}
	});
};
