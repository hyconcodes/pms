<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>403 - Forbidden | {{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600" rel="stylesheet" />
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center">
        <div class="max-w-xl w-full px-4">
            <div class="text-center">
                <div class="flex justify-center mb-8">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-24 h-24 text-green-600">
                        <path
                            fill="currentColor"
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M12 2L2 6v12l10 4 10-4V6l-10-4zM4 7.333L12 4l8 3.333v9.334L12 20l-8-3.333V7.333z"
                        />
                        <path
                            fill="currentColor"
                            d="M12 11a2 2 0 100-4 2 2 0 000 4z"
                        />
                        <path
                            fill="currentColor"
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M12 13c-2.667 0-5 1.333-5 4h10c0-2.667-2.333-4-5-4z"
                        />
                    </svg>
                </div>
                <h1 class="text-6xl font-bold text-gray-900 mb-4">403</h1>
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Access Forbidden</h2>
                <p class="text-gray-600 mb-8">Sorry, you don't have permission to access this page.</p>
                <div class="space-x-4">
                    <a href="{{ url()->previous() }}" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Go Back
                    </a>
                    <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Homepage
                    </a>
                    @auth
                        @role('super-admin')
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                Dashboard
                            </a>
                        @endrole
                        @role('patient')
                            <a href="{{ route('patient.dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                Patient Dashboard
                            </a>
                        @endrole
                        @role(['doctor', 'nurse'])
                            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                Admin Dashboard
                            </a>
                        @endrole
                    @endauth
                    <a href="{{ route('logout') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>
