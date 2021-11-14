const colors = require('tailwindcss/colors')

// commented colors from garden theme
const jsek = {
  'primary': colors.sky[800], //'#29588d',
  'primary-focus': colors.sky[900], //'#214875',
  'primary-content': colors.sky[50], //'#bdd3ec'
  "secondary":  colors.blueGray[300], //"#8bb1dd",
  "secondary-focus": colors.blueGray[400], // "#6497d2",
  "secondary-content": colors.blueGray[900], //"#fff",
  "accent": colors.violet[300], // "#fae5e5",
  "accent-focus": colors.violet[500], //"#f4bebe",
  "accent-content": colors.violet[50],//"#322020",
  "neutral": colors.warmGray[600], //"#5d5656",
  "neutral-focus": colors.warmGray[700], //"#2a2727",
  "neutral-content": colors.warmGray[50], //"#e9e7e7",
  "base-100": colors.warmGray[100], //"#e9e7e7",
  "base-200": colors.warmGray[200], //"#d1cccc",
  "base-300": colors.warmGray[300], //"#b9b1b1",
  "base-content": colors.warmGray[800], //"#100f0f",
  "info": colors.blue[400], //"#2094f3",
  "success": colors.emerald[400], // "#009485",
  "warning": colors.orange[400], //"#ff9900",
  "error": colors.red[400], //"#ff5724",
  "--border-color": "var(--b3)",
  "--rounded-box": ".75rem",
  "--rounded-btn": ".5rem",
  "--rounded-badge": "1.9rem",
  "--animation-btn": ".25s",
  "--animation-input": ".2s",
  "--btn-text-case": "uppercase",
  "--btn-focus-scale": ".95",
  "--navbar-padding": ".75rem",
  "--border-btn": "1px",
  "--tab-border": "1px",
  "--tab-radius": ".5rem"
};

const ameisli = {
  'primary': colors.lime[800], // '#87a05c',
  'primary-focus': colors.lime[900], //'#607440',
  'primary-content': colors.lime[50] //'#B3BE9E'
}

const jungschi = {
  'primary': colors.orange[800], // '#aa5060',
  'primary-focus': colors.orange[900], // '#8b3444',
  'primary-content': colors.orange[50] // '#c9a4ab'
}

const eagles = {
  'primary': colors.yellow[700], // '#DBA944',
  'primary-focus': colors.yellow[800], //'#CD9425',
  'primary-content': colors.yellow[50] //'#ECDEC3'
}

module.exports = {
  purge: {
    content: [
      'templates/**/*.twig',
      '../../pages/**/*.md',
      'svg/*.svg',
    ],
    options: {
      safelist: [
        /data-theme$/,
      ]
    },
  },
  theme: {
    extend: {
      typography: {
        DEFAULT: {
          css: {
            a: {
              'text-decoration': 'none',
              'font-weight': 'inherit',
              color: 'hsla(var(--p)) !important',
              '&:hover': {
                'text-decoration': 'underline',
                color: 'hsla(var(--pf)) !important',
              },
            },
          },
        },
      },
    }
  },
  daisyui: {
    themes: [
      {
        'jsek': jsek,
        'ameisli': {...jsek, ...ameisli},
        'jungschi': {...jsek, ...jungschi},
        'eagles': {...jsek, ...eagles}
      }
    ],
  },
  corePlugins: {
    container: false,
  },
  plugins: [
    require('@tailwindcss/typography'),
    require('daisyui'),
    require('tailwind-heropatterns')({
      patterns: ['topography', 'brick-wall', 'texture', 'bubbles', 'rain', 'parkay-floor', 'bamboo'],
      colors: {
        'default': '#fff'
      },
      opacity: {
        default: '.2',
        "80": ".8"
      },
    }),
    ({ addUtilities, theme }) => {
      addUtilities({
        ".container": {
          maxWidth: theme("screens.sm"),
          "@screen md": {
            maxWidth: theme("screens.md"),
          },
          "@screen lg": {
            maxWidth: theme("screens.lg"),
          },
        },
        ".container-xl": {
          // "@screen md": {
          //   maxWidth: theme("screens.md"),
          // },
          "@screen lg": {
            maxWidth: theme("screens.lg"),
          },
          "@screen xl": {
            maxWidth: theme("screens.xl"),
          },
        },
      })
    }
  ]
}