const path = require('path');
const webpack = require('webpack');

module.exports = {
    entry: './player.js',
    output: {
        path: path.resolve(__dirname),
        filename: 'paella-player.min.js',
        sourceMapFilename: 'paella-player.min.js.map',
        globalObject: 'this',
        library: {
            name: 'PaellaPlayer',
            type: 'umd'
        },

    },
    // devtool: 'source-map',
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /(node_modules)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env']
                    }
                }
            },
            {
                test: /paella-core\.css$/,
                include: path.resolve(
                    __dirname,
                    'node_modules/@asicupv/paella-core/dist'
                ),
                enforce: 'pre',
                use: {
                    loader: path.resolve(
                        __dirname,
                        'loaders/scope-paella-core-css.js'
                    )
                }
            },
            {
                test: /\.css$/,
                use: ['style-loader', 'css-loader']
            },

            {
                test: /\.svg$/i,
                use: {
                    loader: 'svg-inline-loader'
                }
            }
        ]
    },

    optimization: {
        splitChunks: false,
    },
    plugins: [
        new webpack.optimize.LimitChunkCountPlugin({
            maxChunks: 1,
        }),
    ],

    performance: {
        hints: false,
        maxEntrypointSize: 4194304,
        maxAssetSize: 4194304
    }
};
