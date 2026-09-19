const path = require("path");
const webpackConfig = require("@nextcloud/webpack-vue-config");

webpackConfig.entry = {
  main: path.join(__dirname, "src", "main.js"),
  public: path.join(__dirname, "src", "public.js"),
};

webpackConfig.resolve = webpackConfig.resolve || {};
webpackConfig.resolve.modules = [
  path.resolve(__dirname, "node_modules"),
  path.resolve(__dirname, "../projectcreatoraio/node_modules"),
  "node_modules",
];

module.exports = webpackConfig;
