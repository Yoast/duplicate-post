/* global require, process */
const { flattenVersionForFile } = require( './config/webpack/paths' );
const path = require( "path" );

module.exports = function( grunt ) {
	'use strict';

//	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown');

	const pkg = grunt.file.readJSON( './package.json' );
	const pluginVersion = pkg.version;

	const project = {
		pluginVersion: flattenVersionForFile( pluginVersion ),
		pluginSlug: "duplicate-post",
		pluginMainFile: "duplicate-post.php",
		paths: {
			get config() {
				return this.grunt + 'task-config/';
			},
			grunt: 'config/grunt/',
			images: 'images/',
			js: 'js/',
		},
		files: {
			images: [
				'images/*'
			],
			js: [
				'js/src/*.js',
				'!js/dist/*.min.js'
			],
			get config() {
				return project.paths.config + '*.js';
			},
			grunt: 'Gruntfile.js'
		},
		pkg,
		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.wordpress.md': 'readme.txt'
				},
			},
			options: {
				screenshot_url: 'assets/{screenshot}.jpg'
			}
		},
	};

	// Load Grunt configurations and tasks
	require( 'load-grunt-config' )( grunt, {
		configPath: path.join( process.cwd(), project.paths.config ),
		data: project,
		jitGrunt: {
			staticMappings: {
				wp_readme_to_markdown: 'grunt-wp-readme-to-markdown',
			},
			customTasksDir: 'config/grunt/custom-tasks',
		}
	});

//	grunt.initConfig(
//		{
//			pkg: grunt.file.readJSON( 'package.json' ),
//			wp_readme_to_markdown: {
//				your_target: {
//					files: {
//						'README.wordpress.md': 'readme.txt'
//					},
//				},
//				options: {
//					screenshot_url: 'assets/{screenshot}.jpg'
//				}
//			},
//		}
//	);
//
//	grunt.registerTask(
//		'default',
//		[
//			'wp_readme_to_markdown'
//		]
//	);
};
