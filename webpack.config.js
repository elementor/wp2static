var path = require('path');
var webpack = require('webpack');

module.exports = {
    entry: './js/wp2static-admin.ts',
    resolve: {
        extensions: [".webpack.js", ".web.js", ".js", ".ts"],
        alias: { vue: 'vue/dist/vue.esm.js' }
    },
    output: {
        path: path.resolve(__dirname, 'admin'),
        filename: 'wp2static-admin.js',
        library: 'WP2Static'
    },
    module: {
        rules: [
            {
                test: /\.ts$/,
                loader: 'ts-loader'
            }
        ]
    },
    devtool: 'source-map',
    mode: 'development' // change when building
};
