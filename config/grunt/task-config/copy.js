// See https://github.com/gruntjs/grunt-contrib-copy
module.exports = {
	artifact: {
		files: [
			{
				expand: true,
				cwd: ".",
				src: [
					"css/**",
					"js/dist/**/*.js",
					"compat/**",
					"languages/**",
					"src/**",
					"vendor/**",
					"gpl-2.0.txt",
					"readme.txt",
					"duplicate-post.php",
					"duplicate-post-admin.php",
					"duplicate-post-common.php",
					"duplicate-post-options.php",
					"!vendor/bin/**",
					"!vendor/composer/installed.json",
					"!vendor/composer/installers/**",
					"!**/composer.json",
					"!**/README.md",
				],
				dest: "<%= files.artifact %>",
			},
		],
	},
};
