<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Document Review</title>
<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .card {
        background: #ffffff;
        padding: 40px 30px;
        text-align: center;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        width: 350px;
    }

    .icon {
        margin-bottom: 20px;
    }

    .icon svg {
        width: 40px;
        height: 40px;
        stroke: #333;
    }

    .text {
        font-size: 16px;
        color: #333;
        margin-bottom: 25px;
    }

    .btn {
        display: inline-block;
        padding: 12px 25px;
        font-size: 14px;
        color: #fff;
        text-decoration: none;
        border-radius: 6px;
        background: linear-gradient(90deg, #3b2cff, #1f00ff);
        transition: 0.3s ease;
    }

    .btn:hover {
        opacity: 0.9;
    }
</style>
</head>
<body>

<div class="card">
    <div class="icon">
        <!-- Pencil SVG Icon -->
        <svg fill="none" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M15.232 5.232l3.536 3.536M9 11l6.364-6.364a2 2 0 112.828 2.828L11.828 13.828a4 4 0 01-1.414.94l-3.414 1.138 1.138-3.414A4 4 0 019 11z" />
        </svg>
    </div>

    <div class="text">
        Contracts sent you a document to review and sign.
    </div>

    <a href="{{ $signingLink }}" class="btn">Review Document</a>
</div>

</body>
</html>

