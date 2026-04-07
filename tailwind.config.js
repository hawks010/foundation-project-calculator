export default {
  content: ['./src/admin/**/*.{js,jsx}'],
  corePlugins: {
    preflight: false,
  },
  theme: {
    extend: {
      colors: {
        ink: {
          50: '#f8f4ec',
          100: '#eee6d8',
          500: '#9f7f52',
          900: '#171513',
        },
        reef: {
          400: '#2dd4bf',
          500: '#14b8a6',
          700: '#0f766e',
        },
        slateboard: {
          900: '#0d1117',
          850: '#111827',
          800: '#182231',
          700: '#263345',
        },
      },
      boxShadow: {
        soft: '0 24px 80px rgba(15, 23, 42, 0.18)',
        night: '0 24px 80px rgba(0, 0, 0, 0.36)',
      },
      fontFamily: {
        sans: ['ui-sans-serif', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
