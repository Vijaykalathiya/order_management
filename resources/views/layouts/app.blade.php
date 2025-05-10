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
        <div class="container">
            <a href="/" class="logo">Order</a>
            <input type="checkbox" id="menu-toggle" class="menu-toggle">
            <label for="menu-toggle" class="menu-icon">&#9776;</label>
            <ul class="nav-links">
                <li><a href="/">Home</a></li>
                <li><a href="/products/import">Product Import</a></li>
                <li><a href="/order/search-product">POS</a></li>
                <li><a href="/order/report">Report</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="content">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer">
        © {{ date('Y') }} Order. All rights reserved.
    </footer>

    @yield('scripts')
</body>
</html>
