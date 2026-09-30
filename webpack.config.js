const path = require('path')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')

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
    // Production shipped eval-source-map, which roughly doubled the bundle and
    // relies on eval(), so the script is refused under a strict
    // Content-Security-Policy. Source maps stay on for development.
    devtool: isProduction ? false : 'eval-source-map',
  }
}
