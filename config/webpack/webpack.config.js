const CaseSensitivePathsPlugin = require( "case-sensitive-paths-webpack-plugin" );

const {
	camelCaseDash,
} = require( "@wordpress/dependency-extraction-webpack-plugin/lib/util" );

const paths = require( "./paths" );
const pkg = require( "../../package.json" );

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
	"@wordpress/edit-post",
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


function getOutputFilename( mode ) {
	const pluginVersionSlug = paths.flattenVersionForFile( pkg.yoast.pluginVersion );

	return "[name]-" + pluginVersionSlug + ".js";
}

module.exports = ( env = { environment: "production" } ) => {
	const mode = env.environment || process.env.NODE_ENV || "production";

	const config = {
		mode,
		devtool: mode === "development" ? "cheap-module-eval-source-map" : false,
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
			filename: getOutputFilename( mode ),
			jsonpFunction: "duplicatePostWebpackJsonp",
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
		plugins: [
			new CaseSensitivePathsPlugin(),
		],
		optimization: {
			runtimeChunk: {
				name: "commons",
			},
		},
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
