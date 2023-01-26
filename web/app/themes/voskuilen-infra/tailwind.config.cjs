// https://tailwindcss.com/docs/configuration
module.exports = {
  content: ['./index.php', './app/**/*.php', './resources/**/*.{php,vue,js}'],
  safelist: [
    'pt-0',
    'pb-0',
  ],
  theme: {
    extend: {
      container: {
        center: true,
        padding: {
          DEFAULT: '1.5rem',
        },
        screens: {
          sm: '100%',
          md: '980px',
          lg: '1100px',
          xl: '1240px',
        },
      },
      fontFamily: {
        'sans': 'Sofia Pro',
      },
      colors: {},
    },
  },
  plugins: [],
};
