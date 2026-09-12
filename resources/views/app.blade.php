<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
        <meta name="description" content="StockEdp — Sistem Informasi Manajemen Persediaan, Pergudangan & ERP Terpadu">
        <meta name="theme-color" content="#2563eb">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="StockEdp">

        <title>{{ config('app.name', 'StockEdp — Sistem Inventory') }}</title>

        <link rel="icon" type="image/svg+xml" href="/icon.svg">
        <link rel="apple-touch-icon" href="/icon.svg">
        <link rel="manifest" href="/manifest.json">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full antialiased font-sans text-gray-900 bg-gray-50 select-text">
        <div id="app" class="min-h-full flex flex-col"></div>
    </body>
</html>
