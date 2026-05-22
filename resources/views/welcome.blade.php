<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="refresh" content="0;url={{ url('/admin') }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'ERP') }}</title>
    </head>
    <body>
        <p><a href="{{ url('/admin') }}">Ir al panel</a></p>
    </body>
</html>
