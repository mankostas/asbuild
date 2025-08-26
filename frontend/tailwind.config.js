const baseConfig = require('../dashcode-full-source-code/tailwind.config.js');

module.exports = {
  ...baseConfig,
  content: ['./index.html', './src/**/*.{vue,js,ts}'],
};
