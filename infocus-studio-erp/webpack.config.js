const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		dashboard: path.resolve( __dirname, 'src/dashboard/index.js' ),
		'entity-app': path.resolve( __dirname, 'src/entity-app/index.js' ),
		workflow: path.resolve( __dirname, 'src/workflow/index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
};
