/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.{php,html,js}",
    "./includes/*.{php,html,js}",
    "./style/*.{css,js}"
  ],
  theme: {
    extend: {
        colors: {
            body: '#09090b', /* zinc-950 */
            surface: '#18181b', /* zinc-900 */
            card: '#27272a', /* zinc-800 */
            primary: '#14b8a6', /* teal-500 */
            'primary-dim': 'rgba(20, 184, 166, 0.3)',
            'primary-glow': 'rgba(20, 184, 166, 0.12)',
            border: '#3f3f46', /* zinc-700 */
            'border-dim': 'rgba(255,255,255,0.05)',
            txt: '#e4e4e7', /* zinc-200 */
            muted: '#a1a1aa', /* zinc-400 */
            'json-string': '#14b8a6',
            'json-literal': '#f59e0b',
        },
        fontFamily: {
            sans: ['Outfit', 'system-ui', 'sans-serif'],
            mono: ['JetBrains Mono', 'Fira Code', 'monospace'],
        },
        boxShadow: {
            'glow': '0 0 4px rgba(20, 184, 166, 0.4), 0 0 12px rgba(20, 184, 166, 0.2)',
            'modal': '0 20px 50px rgba(0,0,0,0.8), 0 0 0 1px rgba(20, 184, 166, 0.2)',
            'card': '0 8px 30px rgba(0,0,0,0.4)',
        },
        animation: {
            'spin-loader': 'loaderSpin 1s linear infinite',
            'flash': 'flashPulse 0.3s ease',
        },
        keyframes: {
            loaderSpin: {
                '0%': { transform: 'rotate(0deg)' },
                '100%': { transform: 'rotate(360deg)' },
            },
            flashPulse: {
                '0%, 100%': { opacity: '1' },
                '50%': { opacity: '0.5', background: 'rgba(20, 184, 166, 0.2)' },
            },
        },
    }
  },
  plugins: [],
}
