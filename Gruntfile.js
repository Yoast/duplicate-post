module.exports = function( grunt ) {
	'use strict';

	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown')
	grunt.loadNpmTasks( 'grunt-contrib-compress' );

	grunt.initConfig(
		{
			pkg: grunt.file.readJSON( 'package.json' ),
			wp_readme_to_markdown: {
				your_target: {
					files: {
						'README.wordpress.md': 'src/readme.txt'
					},
				},
				options: {
					screenshot_url: 'assets/{screenshot}.jpg'
				}
			},
			compress: {
				build: {
					options: {
						archive: 'dist/<%= pkg.name %>-snapshot.tar.gz',
					},
					expand: true,
					cwd: 'src/',
					src: ['**/*'],
					dest: '<%= pkg.name %>/'
				}
			}
		}
	),

	grunt.registerTask(
		'default',
		[
			'wp_readme_to_markdown', 'compress:build'
		]
	);
}
