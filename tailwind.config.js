/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.{html,php,twig}",
    "./src/**/*.{html,php}",
    "./*.php",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          light: "#4299e1",
          DEFAULT: "#3182ce",
          dark: "#2c5282",
        },
        secondary: {
          light: "#9ae6b4",
          DEFAULT: "#48bb78",
          dark: "#276749",
        },
      },
      fontFamily: {
        sans: [
          "Inter",
          "ui-sans-serif",
          "system-ui",
          "-apple-system",
          "sans-serif",
        ],
      },
      boxShadow: {
        smooth:
          "0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)",
        "smooth-lg":
          "0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)",
      },
    },
  },
  plugins: [],
};
