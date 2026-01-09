<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            text-align: center;
        }
        h1 {
            font-size: 5rem;
            margin-bottom: 20px;
            color: #dc3545; /* Red color for emphasis */
        }
        p {
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        a {
            color: #007bff; /* Blue color for links */
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>404 - Page Not Found</h1>
        <p>The page you are looking for does not exist.</p>
        <a href="{{ url('/admin/dashboard') }}">Go to Home Page</a>
    </div>
</body>
</html>
