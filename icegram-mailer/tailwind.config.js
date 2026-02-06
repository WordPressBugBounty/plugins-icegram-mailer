module.exports = {
  content: [
    './**/*.php',
    './src/**/*.js',
    './**/*.css'
  ],
  theme: {
    extend: {
      colors: {
        'primary': 'var(--brand-color)',
        'secondary': 'var(--secondary)',
        'light-gray': 'var(--light-gray)',
        'lighter-gray': 'var(--lighter-gray)',
        'tertiary': 'var(--brand-color-2)',
      },
    },
  },
  plugins: [
    require('postcss-nesting')
  ],
};
