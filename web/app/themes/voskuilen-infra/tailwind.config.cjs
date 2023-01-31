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
      },
      fontFamily: {
        'sans': 'Sofia Pro',
      },
      colors: {
        primary: '#D0103A',
        offwhite: '#FCF3F5',
      },
    },
  },
  plugins: [],
};
