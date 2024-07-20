const reactJSXRuntimePolyfill = {
	entry: {
		'react-jsx-runtime': {
			import: 'react/jsx-runtime',
		},
	},
	output: {
		path: `/`,
		filename: 'react-jsx-runtime.js',
		library: {
			name: 'ReactJSXRuntime',
			type: 'window',
		},
	},
	externals: {
		react: 'React',
	},
        // Other config...
};

module.exports = [ reactJSXRuntimePolyfill ];