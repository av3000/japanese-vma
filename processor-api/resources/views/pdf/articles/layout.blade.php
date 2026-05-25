<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
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

        @font-face {
            font-family: 'IPAGothic';
            font-style: normal;
            font-weight: bold;
            src: url('/usr/share/fonts/opentype/ipafont-gothic/ipag.ttf') format('truetype');
        }

        body {
            margin: 0;
            color: #111827;
            font-family: 'HanaMinA', 'IPAGothic', sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }

        .japanese,
        [lang='ja'] {
            font-family: 'HanaMinA', 'IPAGothic', sans-serif;
        }

        a {
            color: #1d4ed8;
            text-decoration: none;
        }

        .header {
            border-bottom: 1px solid #d1d5db;
            margin-bottom: 24px;
            padding-bottom: 12px;
        }

        .meta {
            color: #4b5563;
            font-size: 10px;
            margin: 0 0 4px;
        }

        .links {
            margin-top: 12px;
        }

        .links a {
            display: inline-block;
            margin-right: 16px;
        }

        .content {
            margin-bottom: 24px;
            width: 100%;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .content p {
            white-space: normal;
            word-break: break-word;
            word-wrap: break-word;
            overflow-wrap: break-word;
            width: 100%;
            display: block;
        }

        th {
            padding: 6px 0px;
            text-align: left;
            vertical-align: top;
        }

        td {
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .kanjis-table {
            width: 100%;
        }

        .value-line {
            display: block;
        }

        .japanese {
            font-size: 16px;
        }

        .footer {
            border-top: 1px solid #d1d5db;
            color: #6b7280;
            font-size: 10px;
            margin-top: 32px;
            padding-top: 12px;
        }
    </style>
</head>

<body>
    @yield('body')
</body>

</html>
