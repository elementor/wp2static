var path = require('path');
var webpack = require('webpack');

module.exports = {
    entry: {
        main: './js/wp2static-admin.ts',
    },
    resolve: {
        extensions: [".webpack.js", ".web.js", ".js", ".ts"]
    },
    output: {
        //publicPath: "/admin/",
        path: path.resolve(__dirname, 'admin'),
        filename: 'wp2static-admin.js'
    },
    module: {
        rules: [
            {
                test: /\.ts$/,
                loader: 'ts-loader'
            }
        ]
    },
    plugins: [
        new webpack.ProvidePlugin({
            $: 'jquery/src/jquery',
            jquery: 'jquery/src/jquery'
        })
    ],
    devtool: 'source-map',
    externals: {
      jquery: 'jQuery'
    }
};
