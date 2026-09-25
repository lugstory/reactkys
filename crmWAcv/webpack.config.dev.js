const path = require('path');
const ESLintPlugin = require('eslint-webpack-plugin');
const webpack = require('webpack');
const { merge } = require('webpack-merge');
const common = require('./webpack.config.common');

module.exports = () => merge(common, {
  mode: 'development',
  devtool: 'inline-source-map',
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname, 'dist'),
  },
  devServer: {
    historyApiFallback: true,
    port: 3000,
    host: '0.0.0.0',
    open: false,
    client: {
      logging: 'verbose',
      overlay: {
        errors: true,
        warnings: false,
      },
      progress: true,
    },
    server: {
      type: 'http',
    },
  },
  plugins: [
    new webpack.DefinePlugin({
      PRODUCTION: JSON.stringify(false),
    }),
    new ESLintPlugin({
      extensions: ['js', 'jsx'],
    }),
  ],
  module: {
    rules: [
      {
        test: /\.css$/,
        use: [
          {
            loader: 'style-loader',
            options: {
              injectType: 'singletonStyleTag',
            },
          },
          {
            loader: 'css-loader',
            options: {
              sourceMap: true,
            },
          },
          'postcss-loader',
        ],
      },
    ],
  },
});
