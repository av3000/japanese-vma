<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        body {
            margin: 0;
            color: #111827;
            font-family: 'Noto Sans CJK JP', sans-serif;
            font-size: 12px;
            line-height: 1.5;
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
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            border-bottom: 1px solid #9ca3af;
            text-align: left;
        }

        td,
        th {
            padding: 6px 8px;
            vertical-align: top;
        }

        td {
            border-bottom: 1px solid #e5e7eb;
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
