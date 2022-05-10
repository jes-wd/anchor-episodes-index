const path = require('path')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')

module.exports = {
  entry: './src/index.js',
  output: {
    filename: 'jesaei.bundle.js',
    path: path.resolve(__dirname, 'dist'),
    publicPath: './',
  },
  watch: true,
  module: {
    rules: [
      {
        test: /\.(s(a|c)ss)$/,
        enforce: "pre",
        use: [MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader'],
      },
    ],
  },
  plugins: [new MiniCssExtractPlugin()],
  devtool: 'eval-source-map',

}
