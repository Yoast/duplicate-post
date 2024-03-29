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
					"admin-functions.php",
					"common-functions.php",
					"options.php",
					"duplicate_post_yoast_icon-125x125.png",
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
