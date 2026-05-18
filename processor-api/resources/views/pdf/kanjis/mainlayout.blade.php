<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>@yield('title')</title>
    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <style type="text/css">
        @font-face {
            font-family: 'HanaMinA';
            font-style: normal;
            font-weight: normal;
            src: url('/usr/share/fonts/truetype/hanazono/HanaMinA.ttf') format('truetype');
        }

        @font-face {
            font-family: 'IPAGothic';
            font-style: normal;
            font-weight: normal;
            src: url('/usr/share/fonts/opentype/ipafont-gothic/ipag.ttf') format('truetype');
        }

        body,
        html {
            margin: 0;
            font-family: 'HanaMinA', 'IPAGothic', sans-serif;
        }

        .japanese,
        [lang='ja'] {
            font-family: 'HanaMinA', 'IPAGothic', sans-serif;
        }

        table {
            border-spacing: 0;
        }

        thead {
            display: table-header-group
        }

        tfoot {
            display: table-row-group
        }

        tr {
            page-break-after: always;
            page-break-inside: avoid;
        }

        td {
            padding: 0;
        }

        #kanjiTd {
            font-size: 1.5rem;
        }

        .kanjis-table {
            overflow-x: visible !important;
        }

        @media print {
            table {
                overflow: visible !important;
            }
        }
    </style>
</head>

<body>
    <section class="text-center row">
        <div class="col-md-12 mb-5">
            @yield('links')
        </div>
    </section>
    <section class="col-md-12 mb-5">
        @yield('content')
    </section>
    @include('pdf.kanjis.footer')
</body>

</html>
