module.exports = {
  theme: {},
  daisyui: {
    themes: [
      'garden'
    ],
  },
  corePlugins: {
    container: false,
  },
  plugins: [
    require('@tailwindcss/typography'),
    require('daisyui'),
    require('tailwind-heropatterns')({
      patterns: ['topography', 'brick-wall'],
      colors: {
        'default': '#fff'
      },
      opacity: {
        default: '0.4'
      },
    }),
    ({ addComponents, theme }) => {
      addComponents({
        ".container": {
          paddingInline: theme("spacing.4"),
          maxWidth: theme("screens.sm"),
          "@screen md": {
            maxWidth: theme("screens.md"),
          },
          "@screen lg": {
            maxWidth: theme("screens.lg"),
          },
        },
      })
    }
  ]
}