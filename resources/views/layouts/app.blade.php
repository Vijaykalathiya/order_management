<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'My App')</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @yield('css')
</head>
    
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container flex items-center justify-between">
            <a href="/" class="logo font-bold text-xl">Order</a>

            <input type="checkbox" id="menu-toggle" class="menu-toggle hidden">
            <label for="menu-toggle" class="menu-icon cursor-pointer text-2xl">&#9776;</label>

            <ul class="nav-links flex space-x-4 items-center">
                @auth
                    {{-- <li><a href="/">Home</a></li> --}}
                    <li><a href="/products/import">Product Import</a></li>
                    <li><a href="/order/search-product">POS</a></li>
                    <li><a href="/order/report">Report</a></li>

                    <!-- Show username and role -->
                    <li class="logged-in-user">
                        <span>Hi, {{ auth()->user()->name }} ({{ auth()->user()->role }})</span>
                    </li>

                    <!-- Logout button -->
                    <li>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                                Logout
                            </button>
                        </form>
                    </li>
                @else
                    <li>
                        <a href="{{ route('login') }}" class="bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700">
                            Login
                        </a>
                    </li>
                @endauth
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="content py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer text-center mt-10">
        © {{ date('Y') }} Order. All rights reserved.
    </footer>

    @yield('scripts')
</body>
</html>
