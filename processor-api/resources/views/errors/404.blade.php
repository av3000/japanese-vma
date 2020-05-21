<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style type="text/css">
        body {
            background-color: #f89862;
            color: #303030;
            margin: 0;
        }
        h1 {
            font-size: 5rem;
        }
        a {
            color: #3058A4;
        }
        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
        p {
            display: block;
            box-shadow: -1px 0px 0px rgba(0, 0, 0, 0.06);
            width: 100%;
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>
            Masaka! {{$error}}
        </h1>
    </header>
    <section>
        <article>
            <p>
                The page you were looking for doesn't exist.
            </p>
            <p>
                You may have mistyped the address or the page may have moved
            </p>
            <a href="{{ url('/')}}">Go back to main</a>
        </article>
    </section>
</div>
</body>
</html>