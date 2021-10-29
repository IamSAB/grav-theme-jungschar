const base = {
  "primary": "#5c7f67",
  "primary-focus": "#48614f",
  "primary-content": "#ffffff",
  "secondary": "#8bb1dd",
  "secondary-focus": "#6497d2",
  "secondary-content": "#fff",
  "accent": "#fae5e5",
  "accent-focus": "#f4bebe",
  "accent-content": "#322020",
  "neutral": "#5d5656",
  "neutral-focus": "#2a2727",
  "neutral-content": "#e9e7e7",
  "base-100": "#e9e7e7",
  "base-200": "#d1cccc",
  "base-300": "#b9b1b1",
  "base-content": "#100f0f",
  "info": "#2094f3",
  "success": "#009485",
  "warning": "#ff9900",
  "error": "#ff5724",
  "--border-color": "var(--b3)",
  "--rounded-box": ".75rem",
  "--rounded-btn": ".5rem",
  "--rounded-badge": "1.9rem",
  "--animation-btn": ".25s",
  "--animation-input": ".2s",
  "--btn-text-case": "uppercase",
  "--btn-focus-scale": ".95",
  "--navbar-padding": ".5rem",
  "--border-btn": "1px",
  "--tab-border": "1px",
  "--tab-radius": "0.5rem"
};

const jsek = {
  'primary': '#29588d',
  'primary-focus': '#214875',
  'primary-content': '#bdd3ec'
};

const ameisli = {
  primary: '#87a05c',
  'primary-focus': '#607440',
  'primary-content': '#B3BE9E'
}

const jungschi = {
  primary: '#aa5060',
  'primary-focus': '#8b3444',
  'primary-content': '#c9a4ab'
}

const eagles = {
  primary: '#DBA944',
  'primary-focus': '#CD9425',
  'primary-content': '#ECDEC3'
}

module.exports = {
  purge: {
    content: [
      'templates/**/*.twig',
      '../../pages/**/*.md'
    ],
    options: {
      safelist: [
        /data-theme$/,
      ]
    },
  },
  daisyui: {
    themes: [
      {
        'jsek': {...base, ...jsek},
        'ameisli': {...base, ...ameisli},
        'jungschi': {...base, ...jungschi},
        'eagles': {...base, ...eagles}
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
      patterns: ['topography', 'brick-wall', 'texture'],
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