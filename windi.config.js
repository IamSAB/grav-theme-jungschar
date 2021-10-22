const colors = require('daisyui/colors');
const { transform } = require('windicss/helpers');

const icons = [
  'bell', 
  'trees', 
  'home', 
  'sun', 
  'support', 
  'menu-left', 
  'calendar-two', 
  'clipboard', 
  'attribution', 
  'corner-up-right', 
  'arrow-right', 
  'corner-right-up'
].map(_ => 'icon-' + _),
  classes = ['fill-current', 'w-10', 'h-10'];

module.exports = {
  extract: {
    include: ['./templates/**/*.twig'],
  },
  safelist: [/data-theme$/, ...icons, ...classes],
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
  variants: {
    fill: ['hover', 'focus']
  },
  daisyui: {
    themes: [
      'garden'
    ],
  },
  plugins: [
    require('windicss/plugin/typography')({
      modifiers: ['sm', 'lg'],
    }),
    transform('daisyui'),
    require('@windicss/plugin-heropatterns')({
      patterns: ['topography', 'brick-wall'],
      colors: {
        'default': '#fff'
      },
      opacity: {
        default: '0.4'
      },
    }),
    require('@windicss/plugin-icons'),
  ]
}