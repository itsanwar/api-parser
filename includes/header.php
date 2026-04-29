<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,user-scalable=no, initial-scale=1.0">
    <meta name="description" content="Raw API Parser Tool - Parse and test HTTP APIs from raw request data">
    <title>🔴..................</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://kit-pro.fontawesome.com/releases/v5.15.1/css/pro.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
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
            }
        }
    </script>

    <!-- Third-party UI libs -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-notify@0.5.4/dist/simple-notify.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/simple-notify@0.5.4/dist/simple-notify.min.js"></script>

    <!-- MD5 Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/md5.min.js"></script>

    <!-- Custom styles -->
    <link rel="stylesheet" href="./style/custom.css">
</head>

<body class="stop text-txt min-h-screen">
    <!-- Loader -->
    <div class="loader show">
        <div class="loader-inner">
            <i></i>
        </div>
    </div>

    <?php ?>