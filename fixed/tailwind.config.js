/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.php",
    "./login.php",
    "./exam.php",
    "./admin/**/*.php",
    "./formateur/**/*.php",
    "./stagiaire/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        'primary': '#3B82F6',
        'secondary': '#10B981',
        'accent': '#6366F1',
        'background': '#F3F4F6',
        'text': '#1F2937'
      },
      fontFamily: {
        'sans': ['Inter', 'system-ui', 'sans-serif']
      },
      boxShadow: {
        'soft': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
        'hover': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)'
      }
    },
  },
  plugins: [],
}
