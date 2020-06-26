<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Vendor JS Files -->
    <script src="{{ asset('js/api-landing/vendor/jquery/jquery.min.js') }}" defer></script>
    <script src="{{ asset('js/api-landing/vendor/bootstrap/js/bootstrap.bundle.min.js') }}" defer></script>
    <script src="{{ asset('js/api-landing/vendor/jquery.easing/jquery.easing.min.js') }}" defer></script>
    <script src="{{ asset('js/api-landing/vendor/php-email-form/validate.js') }}" defer></script>
    <script src="{{ asset('js/api-landing/vendor/counterup/counterup.min.js') }}" defer></script>
    <script src="{{ asset('js/api-landing/vendor/waypoints/jquery.waypoints.min.js') }}" defer></script>
    <script src="{{ asset('js/api-landing/vendor/isotope-layout/isotope.pkgd.min.js') }}" defer></script>
    <script src="{{ asset('js/api-landing/vendor/superfish/superfish.min.js') }}" defer></script>
    <script src="{{ asset('js/api-landing/vendor/hoverIntent/hoverIntent.js') }}" defer></script>
    <script src="{{ asset('js/api-landing/vendor/owl.carousel/owl.carousel.min.js') }}" defer></script>
    <script src="{{ asset('js/api-landing/vendor/venobox/venobox.min.js') }}" defer></script>

    <script src="{{ asset('js/api-landing/vendor/aos/aos.js') }}" defer></script>
    <!-- Template Main JS File -->
    <script src="{{ asset('js/api-landing/js/main.js') }}" defer></script>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>

    @yield('content')

      <!-- ======= Footer ======= -->
    <footer id="footer">
        <div class="footer-top">
        <div class="container">

        </div>
        </div>

        <div class="container">
        <div class="copyright">
            &copy; Copyright <strong>JPLearning</strong>. All Rights Reserved
            <a href="#" class="back-to-top"><i class="fa fa-chevron-up"></i></a>
            <script src="https://kit.fontawesome.com/977323c370.js" crossorigin="anonymous"></script>
        </div>
        </div>
    </footer><!-- End Footer -->
</body>
</html>
