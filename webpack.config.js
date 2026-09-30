const path = require('path')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const TerserPlugin = require('terser-webpack-plugin')

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production'

  return {
    entry: './src/index.js',
    output: {
      filename: 'jesaei.bundle.js',
      path: path.resolve(__dirname, 'dist'),
      publicPath: './',
    },
    module: {
      rules: [
        {
          test: /\.(s(a|c)ss)$/,
          enforce: 'pre',
          use: [MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader'],
        },
      ],
    },
    plugins: [new MiniCssExtractPlugin()],
    optimization: {
      // JavaScript only. Sass already emits compressed CSS, and webpack's CSS
      // minimizer additionally rewrites media queries into range syntax
      // - @media (width>=769px) - which browsers before Chrome 104 and
      // Safari 16.4 ignore entirely, silently dropping the desktop layout.
      // It does not honour browserslist for that transform. This only shows up
      // on a fresh install that resolves a newer webpack, so it is pinned
      // here rather than left to chance.
      minimizer: [new TerserPlugin()],
    },
    // Production shipped eval-source-map, which roughly doubled the bundle and
    // relies on eval(), so the script is refused under a strict
    // Content-Security-Policy. Source maps stay on for development.
    devtool: isProduction ? false : 'eval-source-map',
  }
}
