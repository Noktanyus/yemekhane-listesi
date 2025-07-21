<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Tabldot Bulunamadı!</title>
    <link rel="icon" href="/assets/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .container {
            padding: 2rem;
        }
        .icon {
            font-size: 6rem;
            color: #ffc107;
            margin-bottom: 1rem;
            animation: shake 0.82s cubic-bezier(.36,.07,.19,.97) both;
            transform: translate3d(0, 0, 0);
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
        }
        .btn-home {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: background-color 0.2s;
        }
        .btn-home:hover {
            background-color: #0056b3;
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="fa-solid fa-plate-wheat"></i>
        </div>
        <h1>404 - Tabldot Bulunamadı!</h1>
        <p>Aradığınız sayfa bugünün menüsünde yok gibi görünüyor.</p>
        <a href="/" class="btn-home">Anasayfaya Dön</a>
    </div>
</body>
</html>
