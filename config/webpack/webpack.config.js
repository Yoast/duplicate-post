const {
	camelCaseDash,
} = require( "@wordpress/dependency-extraction-webpack-plugin/lib/util" );

const paths = require( "./paths" );

const externals = {
	// This is necessary for Gutenberg to work.
	tinymce: "window.tinymce",

	// General dependencies that we have.
	lodash: "window.lodash",
	"lodash-es": "window.lodash",
	react: "React",
	"react-dom": "ReactDOM",
};

/**
 * WordPress dependencies.
 */
const wordpressPackages = [
	"@wordpress/a11y",
	"@wordpress/api-fetch",
	"@wordpress/block-editor",
	"@wordpress/blocks",
	"@wordpress/components",
	"@wordpress/compose",
	"@wordpress/data",
	"@wordpress/dom",
	"@wordpress/dom-ready",
	"@wordpress/editor",
	"@wordpress/element",
	"@wordpress/html-entities",
	"@wordpress/i18n",
	"@wordpress/is-shallow-equal",
	"@wordpress/keycodes",
	"@wordpress/plugins",
	"@wordpress/rich-text",
	"@wordpress/server-side-render",
	"@wordpress/url",
];

// WordPress packages.
const wordpressExternals = wordpressPackages.reduce( ( memo, packageName ) => {
	const name = camelCaseDash( packageName.replace( "@wordpress/", "" ) );

	memo[ packageName ] = `window.wp.${ name }`;
	return memo;
}, {} );


function getOutputFilename() {
	return "[name].js";
}

module.exports = ( env = { environment: "production" } ) => {
	const mode = env.environment || process.env.NODE_ENV || "production";

	const config = {
		mode,
		devtool: mode === "development" ? "eval-cheap-module-source-map" : false,
		entry: paths.entry,
		context: paths.jsSrc,
		optimization: {
			minimize: true,
		},
		externals: {
			...externals,
			...wordpressExternals,
		},
		output: {
			path: paths.jsDist,
			filename: getOutputFilename(),
			chunkLoadingGlobal: "duplicatePostWebpackJsonp",
		},
		resolve: {
			extensions: [ ".js", ".jsx" ],
			symlinks: false,
		},
		module: {
			rules: [
				{
					test: /.jsx?$/,
					exclude: /node_modules[/\\](?!(gutenberg|@wordpress|parse5)[/\\]).*/,
					use: [
						{
							loader: "babel-loader",
							options: {
								cacheDirectory: true,
								env: {},
							}
						},
					],
				},
			],
		},
		plugins: [],
	};

	if ( mode === "development" ) {
		config.devServer = {
			publicPath: "/",
		};
		config.optimization = {
			minimize: false,
		};
	}

	return config;
};
