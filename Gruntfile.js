module.exports = function( grunt ) {
	'use strict';

	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );

	grunt.initConfig(
		{
			wp_readme_to_markdown: {
				your_target: {
					files: {
						'README.wordpress.md': 'src/readme.txt'
					},
				},
				options: {
					screenshot_url: 'assets/{screenshot}.png'
				}
			},
		}
	),

	grunt.registerTask(
		'default',
		[
			'wp_readme_to_markdown'
		]
	);
}
