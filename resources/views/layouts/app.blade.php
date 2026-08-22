<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'STETECH AI')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: '#0B0E14',
                        panel: '#131822',
                        line: '#232B38',
                        muted: '#7C8699',
                        brass: '#C99A4A',
                        gain: '#4FAE7C',
                        loss: '#C4574A',
                    },
                    fontFamily: {
                        display: ['"Instrument Serif"', 'serif'],
                        body: ['Inter', 'sans-serif'],
                        mono: ['"IBM Plex Mono"', 'monospace'],
                    },
                }
            }
        }
    </script>
    <style>body{background:#0B0E14;}</style>
</head>
<body class="font-body text-slate-100 antialiased">
    @yield('content')
</body>
</html>
