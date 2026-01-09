<!DOCTYPE html>
<html>
<head>
    <title>Scanned Document</title>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .image-container {
            width: 100%;
            text-align: center;
        }
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="image-container">
        @if(isset($imageBase64))
            <img src="{{ $imageBase64 }}" alt="Scanned Document">
        @else
            <p>No image available</p>
        @endif
    </div>
</body>
</html>