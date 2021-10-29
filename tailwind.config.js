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
      patterns: ['topography', 'brick-wall', 'texture'],
      colors: {
        'default': '#fff'
      },
      opacity: {
        default: '.2',
        "80": ".8"
      },
    }),
    ({ addComponents, theme }) => {
      addComponents({
        ".container": {
          maxWidth: theme("screens.sm"),
          "@screen md": {
            maxWidth: theme("screens.md"),
          },
          "@screen lg": {
            maxWidth: theme("screens.lg"),
          },
        },
      }),
      addComponents({
        ".container-xl": {
          maxWidth: theme("screens.sm"),
          "@screen md": {
            maxWidth: theme("screens.md"),
          },
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