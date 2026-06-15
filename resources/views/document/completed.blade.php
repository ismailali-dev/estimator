<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Signed Packet Ready</title>
    <style>
        :root {
            --panel: rgba(255, 254, 249, 0.94);
            --text: #273009;
            --muted: #66704a;
            --primary: #c9da2b;
            --primary-dark: #93a316;
            --primary-ink: #313a08;
            --border: #d5dd94;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(180deg, #fbfdf0 0%, #edf3ca 100%);
            color: var(--text);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .card {
            width: min(620px, 100%);
            padding: 34px 30px;
            background: var(--panel);
            border-radius: 28px;
            box-shadow: 0 20px 50px rgba(92, 107, 18, 0.12);
            text-align: center;
        }

        .eyebrow {
            margin: 0;
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        h1 {
            margin: 10px 0 14px;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 40px;
            line-height: 1.04;
        }

        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
            font-size: 15px;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 26px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 148px;
            min-height: 48px;
            padding: 0 22px;
            border-radius: 999px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
        }

        .button-primary {
            background: linear-gradient(180deg, #dcea66 0%, var(--primary) 100%);
            color: var(--primary-ink);
            box-shadow: 0 12px 24px rgba(124, 139, 20, 0.18);
        }

        .button-outline {
            background: rgba(255, 254, 249, 0.9);
            border: 1px solid var(--border);
            color: var(--text);
        }
    </style>
</head>
<body>
    <div class="card">
        {{-- <p class="eyebrow">Signed Packet</p> --}}
        <h1>Your documents are ready.</h1>
        <p>
            The signed packet has been generated successfully.
            @if($recipientEmail)
                This link belongs to {{ $recipientEmail }}.
            @endif
        </p>

        <div class="actions">
            <a href="{{ $viewUrl }}" class="button button-primary" target="_blank" rel="noopener noreferrer">View</a>
            <a href="{{ $downloadUrl }}" class="button button-outline">Download</a>
        </div>
    </div>
</body>
</html>
