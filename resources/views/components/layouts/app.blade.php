<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Planning' }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link
            rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap"
        >
        <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
        <style>
            :root {
                color-scheme: light;
            }

            body {
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            }
        </style>
        @stack('head')
    </head>
    <body class="min-h-screen bg-slate-100 font-sans text-slate-900">
        {{ $slot }}
        @stack('scripts')
    </body>
</html>
