<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <style>
            @media print {
                body {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .no-print {
                    display: none !important;
                }
            }
            @page {
                size: landscape;
                margin: 1cm;
            }
        </style>
    </head>
    <body class="min-h-screen bg-white antialiased">
        <div class="p-4">
            {{ $slot }}
        </div>
        @fluxScripts
        <script>
            // Auto-print when loaded (optional - user can trigger manually)
            // window.addEventListener('load', function() { window.print(); });
        </script>
    </body>
</html>
