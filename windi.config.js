const colors = require('daisyui/colors');
const typography = require('windicss/plugin/typography');
const { transform } = require('windicss/helpers');

module.exports = {
  extract: {
    include: ['./templates/**/*.twig'],
  },
  safelist: [/data-theme$/, 'fill-current','w-12', 'h-12'],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        ...colors
      },
      fontFamily: {
        sans: ['Roboto', 'sans-serif'],
        serif: ['Merriweather', 'serif'],
      },
    }
  },
  daisyui: {
    themes: [
      'garden'
    ],
  },
  plugins: [
    require('windicss/plugin/typography'),
    transform('daisyui'),
  ]
}