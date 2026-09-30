@props([
    'title' => 'TMG Ambassadors Space | Become a TMG Ambassador',
    'description' => 'TMG Ambassadors Space — information, endorsed candidate presentation and ambassador registration.',
])

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
        <title>{{ $title }}</title>
        <meta name="description" content="{{ $description }}">
        <meta name="theme-color" content="#07090f">
        <meta name="robots" content="index,follow,max-image-preview:large">

        <meta property="og:type" content="website">
        <meta property="og:title" content="TMG Ambassadors Space | Become a TMG Ambassador">
        <meta property="og:description" content="TMG Ambassadors Space — information, endorsed candidate presentation and ambassador registration.">
        <meta property="og:image" content="{{ asset('assets/og-image.png') }}">
        <meta property="og:image:secure_url" content="{{ asset('assets/og-image.png') }}">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="TMG Ambassadors Space social share card featuring Alhaji Atiku Abubakar">
        <meta property="og:url" content="{{ url()->current() }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="TMG Ambassadors Space | Become a TMG Ambassador">
        <meta name="twitter:description" content="TMG Ambassadors Space — information, endorsed candidate presentation and ambassador registration.">
        <meta name="twitter:image" content="{{ asset('assets/og-image.png') }}">
        <meta name="twitter:image:alt" content="TMG Ambassadors Space social share card featuring Alhaji Atiku Abubakar">

        <link rel="icon" href="{{ asset('assets/favicon.png') }}">

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/ambassadors.css', 'resources/js/ambassadors.js'])
        @endif
    </head>
    <body data-theme="dark">
        {{ $slot }}
    </body>
</html>
