// const colors = require('windicss/colors');
const typography = require('windicss/plugin/typography');
const { transform, defineConfig } = require('windicss/helpers');

module.exports = {
  extract: {
    include: ['./templates/**.twig'],
  },
  safelist: [/data-theme$/],
  darkMode: 'class',
  theme: {
    fontFamily: {
      sans: ['Roboto', 'sans-serif'],
      serif: ['Merriweather', 'serif'],
    },
  },
  daisyui: {
    themes: [
      'garden'
    ],
  },
  plugins: [
    typography,
    transform('daisyui'),
  ]
}