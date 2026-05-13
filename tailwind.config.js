module.exports = {
    content: [
        './src/**/*.{html,js,twig}',
        './templates/**/*.html.twig',
        './assets/**/*.{js,vue}'
    ],
    theme: {
        container: {
            center: true,
            padding: '2rem',
        },
        extend: {
            // Правильное место для кастомных экранов и цветов — внутри extend
            screens: {
                sm: '480px',
                md: '768px',
                lg: '976px',
                xl: '1440px',
            },
            colors: {
                blue: '#1fb6ff',
                purple: '#7e5bef',
                pink: '#ff49db',
                orange: '#ff7849',
                green: '#13ce66',
                yellow: '#ffc82c',
                'gray-dark': '#273444',
                gray: '#8492a6',
                'gray-light': '#d3dce6',
            },
            fontFamily: {
                sans: ['Arial', 'Helvetica Neue', 'Helvetica', 'sans-serif'],
                serif: ['Arial', 'Helvetica Neue', 'Helvetica', 'Georgia', 'serif'],
            },
            spacing: {
                '8xl': '96rem',
                '9xl': '128rem',
            },
            borderRadius: {
                '4xl': '2rem',
            },
            animation: {
                'fade-in': 'fadeIn 0.3s ease-out',
                'fade-in-down': 'fadeInDown 0.4s ease-out',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: 0 },
                    '100%': { opacity: 1 }
                },
                fadeInDown: {
                    '0%': { opacity: 0, transform: 'translateY(-10px)' },
                    '100%': { opacity: 1, transform: 'translateY(0)' }
                }
            }
        }
    },
    plugins: [],
}
