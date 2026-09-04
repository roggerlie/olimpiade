@props(['title' => 'Login'])

<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} | CBT Olimpiade</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-scripts')
</head>

<body class="dark:bg-gray-900" x-data>
    {{ $slot }}
</body>

</html>
