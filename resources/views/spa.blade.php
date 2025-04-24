<!-- resources/views/spa.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Mitra Apps</title>
     <!-- Fonts -->
     <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
      
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
</head>
<body>
    <div id="app"></div>
    <script>
        window.APP_CONFIG = {
            API_BASE_URL: '{{ env('API_BASE_URL', '/api') }}'
        };
    </script>
    @vite(['resources/js/app.js'])
</body>
</html>