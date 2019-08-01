module.exports = function( grunt ) {
	'use strict';

	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown');

	grunt.initConfig(
		{
			pkg: grunt.file.readJSON( 'package.json' ),
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
		}
	);

	grunt.registerTask(
		'default',
		[
			'wp_readme_to_markdown'
		]
	);
};
