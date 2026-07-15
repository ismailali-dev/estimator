<!DOCTYPE html>
<html lang="en">
<head>
    <title>Signature & Document Review</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-light: #f6f3ef;
            --bg-gradient: radial-gradient(circle at 10% 20%, rgba(245, 240, 235, 1) 0%, rgba(239, 234, 227, 1) 100%);
            --card-bg: #ffffff;
            --card-border-light: #f0e7db;
            --card-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.02);
            --card-hover-shadow: 0 28px 40px -16px rgba(0, 0, 0, 0.12), 0 1px 3px rgba(0, 0, 0, 0.03);
            --text-primary: #2c2a29;
            --text-muted: #6e6a66;
            --accent-gold: #b68b40;
            --accent-gold-dark: #9b6e30;
            --accent-soft: #fef5e8;
            --success-emerald: #2d6a4f;
            --success-mint: #e9f5ef;
            --border-soft: #e7dfd7;
            --signature-bg: #fefcf9;
            --btn-dark: #2f2e2b;
        }

        body {
            background: var(--bg-gradient);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', 'Georgia', serif;
            color: var(--text-primary);
            line-height: 1.45;
            -webkit-font-smoothing: antialiased;
        }

        body.modal-open {
            overflow: hidden;
        }

        .hidden {
            display: none !important;
        }

        /* main container */
        .page {
            padding: 2.5rem 1.5rem 4rem;
            max-width: 1280px;
            margin: 0 auto;
        }

        .packet {
            max-width: 980px;
            margin: 0 auto;
        }

        .greeting-section {
            margin-bottom: 2rem;
            text-align: left;
            border-left: 4px solid var(--accent-gold);
            padding-left: 1.25rem;
        }

        .eyebrow {
            display: inline-block;
            background: rgba(182, 139, 64, 0.12);
            color: var(--accent-gold-dark);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 0.2rem 0.9rem;
            border-radius: 40px;
            margin-bottom: 0.75rem;
        }

        .greeting-section h1 {
            font-size: 2.2rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            color: #2b2a27;
            margin-bottom: 0.25rem;
        }

        .greeting-section p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* DOCUMENT CARDS */
        .document-list {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .document-block {
            background: var(--card-bg);
            border-radius: 2rem;
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--card-border-light);
            backdrop-filter: blur(0px);
            position: relative;
            overflow: hidden;
        }

        .document-block::before {
            content: none;
        }

        .document-block:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover-shadow);
            border-color: #e9daca;
        }

        .document-inner {
            padding: 1.6rem 2rem 2rem 2rem;
        }

        .document-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .document-head h2 {
            font-size: 1.75rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #3a3632, #272421);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin: 0;
            font-family: 'Georgia', serif;
        }

        .status-pill {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.3rem 1rem;
            border-radius: 60px;
            background: var(--success-mint);
            color: var(--success-emerald);
            border: 1px solid rgba(45, 106, 79, 0.2);
            backdrop-filter: blur(4px);
        }

        .status-pill.review-only {
            background: #f2ede8;
            color: #8b765c;
            border-color: #e2d5ca;
        }

        .preview-surface {
            margin: 1rem 0 1.2rem 0;
            border-radius: 1.25rem;
            background: #ffffff;
            overflow: hidden;
            border: 0;
            transition: 0.2s;
        }

        .pdf-page, .image-page {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            position: relative;
            overflow: hidden;
        }

        .pdf-page canvas, .image-page img {
            display: block;
            width: 100%;
            height: auto;
            border-radius: 1rem;
        }

        .field-overlay {
            position: absolute;
            border: 0.5px solid rgba(22, 55, 70, 0.18);
            background: rgba(255, 255, 255, 0.92);
            color: #e00000;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-family: Georgia, serif;
            font-weight: 700;
            line-height: 1.05;
            padding: 2px;
            pointer-events: none;
            overflow: hidden;
            z-index: 5;
        }

        .field-overlay.is-signature {
            cursor: pointer;
            pointer-events: auto;
            border: 0.5px solid rgba(22, 55, 70, 0.18);
            background: rgba(255, 255, 255, 0.92);
            padding: 0;
        }

        .field-overlay.is-editable {
            pointer-events: auto;
            padding: 0;
        }

        .field-overlay-input {
            width: 100%;
            height: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: #e00000;
            font: inherit;
            font-weight: 700;
            text-align: center;
            padding: 2px 4px;
            resize: none;
            overflow: hidden;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
            line-height: 1.1;
        }

        .field-overlay-input::placeholder {
            color: #e00000;
            opacity: 0.9;
        }

        .field-overlay-signature-img {
            width: 100%;
            height: 100%;
            object-fit: fill;
            pointer-events: none;
            display: block;
        }

        .signed-badge {
            position: relative;
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            min-width: 190px;
            min-height: 58px;
            padding: 0.35rem 0.75rem 0.28rem;
            border: 2px solid #5a4fff;
            border-radius: 0.45rem;
            background: #fff;
            color: #1f2933;
            font-family: Arial, sans-serif;
            line-height: 1;
        }

        .signed-badge-label {
            position: absolute;
            top: -0.58rem;
            left: 1.8rem;
            padding: 0 0.18rem;
            background: #fff;
            color: #1f2933;
            font-size: 0.66rem;
            font-weight: 700;
        }

        .signed-badge img {
            width: 150px;
            max-width: 100%;
            height: 32px;
            object-fit: contain;
            object-position: left center;
            display: block;
        }

        .signed-badge-code {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #4b5563;
            font-size: 0.58rem;
            font-weight: 600;
            letter-spacing: 0;
        }

        .field-overlay .signed-badge {
            width: 100%;
            height: 100%;
            min-width: 0;
            min-height: 0;
            padding: 0.18rem 0.35rem 0.12rem;
            border-width: 2px;
            border-radius: 0.35rem;
            transform: scale(0.96);
        }

        .field-overlay .signed-badge-label {
            top: -0.43rem;
            left: 1rem;
            font-size: 0.46rem;
        }

        .field-overlay .signed-badge img {
            width: 100%;
            height: calc(100% - 12px);
        }

        .field-overlay .signed-badge-code {
            max-width: 100%;
            font-size: 0.42rem;
        }

        .loading-card, .error-card {
            background: #fffdf9;
            border-radius: 1.25rem;
            padding: 2rem;
            text-align: center;
            font-weight: 500;
            color: var(--text-muted);
        }

        .signature-panel {
            margin-top: 1.5rem;
        }

        .signature-copy {
            font-size: 0.8rem;
            color: var(--accent-gold-dark);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--accent-soft);
            padding: 0.4rem 1rem;
            border-radius: 60px;
            width: fit-content;
        }

        .signature-card {
            background: #ffffff;
            border-radius: 1.5rem;
            border: 1px solid #ede4da;
            padding: 1.4rem 1.7rem;
            transition: all 0.2s;
            box-shadow: 0 10px 18px -12px rgba(0, 0, 0, 0.05);
        }

        .signature-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: #4f463c;
            letter-spacing: -0.2px;
            border-left: 3px solid var(--accent-gold);
            padding-left: 0.7rem;
            margin-bottom: 1rem;
        }

        .signature-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .signature-row-label {
            width: 110px;
            font-weight: 500;
            color: #7f7264;
            font-size: 0.8rem;
        }

        .signature-action {
            flex: 1;
        }

        .signature-preview {
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            background: transparent;
            border: 0;
            border-radius: 0;
            padding: 0;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: none;
        }

        .signature-preview:hover {
            transform: translateY(-1px);
        }

        .signature-preview.is-signed {
            background: transparent;
        }

        .signature-preview img {
            max-height: 40px;
            max-width: 130px;
            object-fit: contain;
        }

        .signature-preview-label {
            font-weight: 500;
            font-size: 0.8rem;
            color: #4f453b;
        }

        .signature-preview-note {
            font-size: 0.65rem;
            color: #9e8f7e;
        }

        .signed-submit-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 2.5rem;
        }

        .signed-submit-date {
            color: #0f172a;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .signature-line {
            border-bottom: 1.5px dashed #decbb8;
            padding: 0.45rem 0;
            min-width: 180px;
            font-weight: 500;
            color: #382e26;
        }

        .signature-meta {
            margin-top: 0.75rem;
            font-size: 0.7rem;
            background: var(--success-mint);
            display: inline-block;
            padding: 0.25rem 0.9rem;
            border-radius: 40px;
            color: var(--success-emerald);
        }

        .submit-bar {
            margin-top: 3rem;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .button {
            background: white;
            border: 1px solid #ddd2c6;
            border-radius: 60px;
            padding: 0.7rem 1.8rem;
            font-weight: 600;
            font-size: 0.85rem;
            transition: 0.2s;
            cursor: pointer;
            font-family: inherit;
        }

        .button-primary {
            background: var(--btn-dark);
            border: none;
            color: white;
            box-shadow: 0 6px 14px rgba(47, 46, 43, 0.2);
            background: linear-gradient(105deg, #2c2a28, #1f1e1b);
        }

        .button-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -12px rgba(0, 0, 0, 0.35);
            background: #1f1e1b;
        }

        /* MODAL LUXURY */
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(25, 23, 21, 0.7);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 1rem;
        }

        .modal.is-open {
            display: flex;
            animation: fadeIn 0.2s ease;
        }

        .modal-card {
            background: #fffcf8;
            border-radius: 2rem;
            max-width: 560px;
            width: 100%;
            padding: 1.8rem;
            border: 1px solid #efdecb;
            box-shadow: 0 30px 45px -20px rgba(0, 0, 0, 0.4);
        }

        .modal-card h3 {
            font-size: 1.6rem;
            font-weight: 600;
            font-family: 'Georgia', serif;
            margin-bottom: 0.4rem;
        }

        .signature-mode-toggle {
            display: inline-flex;
            gap: 0.25rem;
            padding: 0.25rem;
            background: #f4ece3;
            border: 1px solid #e3d4c4;
            border-radius: 60px;
            margin: 0.6rem 0 0.2rem;
        }

        .signature-mode-btn {
            border: 0;
            background: transparent;
            color: #725f4b;
            border-radius: 60px;
            padding: 0.55rem 1.2rem;
            font-weight: 700;
            font-size: 0.78rem;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s ease;
        }

        .signature-mode-btn.is-active {
            background: #2f2e2b;
            color: #ffffff;
            box-shadow: 0 6px 14px rgba(47, 46, 43, 0.18);
        }

        .signature-canvas.is-name-mode {
            cursor: default;
        }

        .signature-canvas {
            width: 100%;
            height: 220px;
            background: #fefaf5;
            border-radius: 1.2rem;
            border: 1px solid #e9ddd0;
            box-shadow: inset 0 0 0 1px rgba(255,255,240,0.8);
            margin: 1rem 0;
            cursor: crosshair;
        }

        .modal-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 0.5rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .success-card {
            background: white;
            max-width: 580px;
            margin: 4rem auto;
            border-radius: 2.5rem;
            padding: 3rem;
            text-align: center;
            border: 1px solid #f0e2d2;
            box-shadow: 0 30px 40px -20px rgba(0,0,0,0.1);
        }

        /* New styles for the second modal overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3000;
        }
        .hidden-modal {
            display: none !important;
        }
        .modal-container {
            background: #fff;
            border-radius: 32px;
            max-width: 540px;
            width: 90%;
            box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            font-family: 'Inter', system-ui, sans-serif;
        }
        .modal-content {
            padding: 1.5rem 1.8rem 2rem;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .dialpad-icon {
            background: #1c3d5f;
            color: white;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-weight: bold;
        }
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #8a7f72;
            transition: 0.2s;
        }
        .close-btn:hover { color: #2c2a29; }
        .title-section {
            margin-bottom: 1.25rem;
        }
        .main-title {
            font-size: 1.7rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .badge-contracts {
            background: #f2e8dc;
            font-size: 0.7rem;
            padding: 4px 12px;
            border-radius: 40px;
            font-weight: 500;
            color: #b68b40;
        }
        .subtitle {
            color: #7f7264;
            font-size: 0.85rem;
            margin-top: 6px;
        }
        .message-body {
            background: #fefaf5;
            border-radius: 24px;
            padding: 1.2rem 1.4rem;
            margin: 1.2rem 0;
            border: 1px solid #f1e5d8;
        }
        .greeting {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .message-text {
            font-size: 0.9rem;
            color: #4a423b;
            line-height: 1.45;
            margin-bottom: 1rem;
        }
        .attachment-badge {
            background: white;
            border-radius: 40px;
            border: 1px solid #e2d5c8;
            padding: 6px 14px;
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 1.2rem;
        }
        .signature-block {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 0.5rem;
            border-top: 1px solid #eee3d8;
            padding-top: 1rem;
        }
        .avatar {
            width: 40px;
            height: 40px;
            background: #c7b59b;
            border-radius: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        .signature-info h4 {
            font-size: 0.9rem;
            font-weight: 700;
        }
        .signature-info .email {
            font-size: 0.7rem;
            color: #8f8070;
        }
        .modal-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1.2rem;
        }
        .dropdown-group {
            display: flex;
            gap: 0.8rem;
        }
        .custom-dropdown {
            position: relative;
        }
        .dropdown-btn {
            background: #fff;
            border: 1px solid #e2d5ca;
            border-radius: 60px;
            padding: 0.5rem 1.2rem;
            font-size: 0.8rem;
            display: flex;
            gap: 8px;
            align-items: center;
            cursor: pointer;
        }
        .chevron {
            font-size: 10px;
        }
        .dropdown-menu {
            position: absolute;
            top: 110%;
            left: 0;
            background: white;
            border-radius: 20px;
            box-shadow: 0 12px 28px rgba(0,0,0,0.1);
            min-width: 150px;
            list-style: none;
            padding: 0.5rem 0;
            margin: 0;
            z-index: 120;
            display: none;
        }
        .dropdown-menu.show {
            display: block;
        }
        .dropdown-menu li {
            padding: 0.5rem 1.2rem;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .dropdown-menu li:hover {
            background: #fcf7f0;
        }
        .continue-btn {
            background: #2c2a28;
            border: none;
            border-radius: 60px;
            padding: 0.65rem 1.8rem;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .continue-btn:hover {
            transform: scale(0.97);
            background: #1a1917;
        }
        .reopen-trigger {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #2c2a28;
            border: none;
            border-radius: 60px;
            padding: 10px 20px;
            color: white;
            font-weight: 600;
            gap: 8px;
            cursor: pointer;
            z-index: 2000;
            box-shadow: 0 8px 18px rgba(0,0,0,0.2);
            display: none;
            align-items: center;
        }

        @media (max-width: 700px) {
            .document-inner {
                padding: 1.2rem;
            }
            .document-head h2 {
                font-size: 1.3rem;
            }
            .signature-row {
                flex-direction: column;
                align-items: flex-start;
            }
            .signature-row-label {
                width: auto;
            }
            .modal-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .dropdown-group {
                justify-content: space-between;
            }
            .field-overlay.is-editable {
                border-width: 1px;
                font-size: clamp(6px, 1.8vw, 10px) !important;
                line-height: 1;
            }
            .field-overlay.is-editable .field-overlay-input {
                padding: 0 2px;
                font-size: inherit !important;
                line-height: 1;
                white-space: pre-wrap;
                overflow-wrap: anywhere;
                word-break: break-word;
                overflow: hidden;
                text-overflow: clip;
            }
        }

    </style>
</head>
<body>

    @php
        $documentsWithSignature = $documents->filter(function ($document) {
            $signatureFields = collect($document->fields ?: [])->filter(function ($field) {
                return is_array($field) && ($field['type'] ?? null) === 'signature';
            });

            return $document->signature_required || $signatureFields->isNotEmpty();
        })->count();

        $packetReference = strtoupper(substr(str_replace('-', '', $token), 0, 24));
        $signerBase = $recipientEmail ? explode('@', $recipientEmail)[0] : 'customer';
        $signerName = preg_replace('/\d+/', '', str_replace(['.', '_', '-'], ' ', $signerBase));
        $signerName = trim(preg_replace('/\s+/', ' ', $signerName));
        $signerName = $signerName !== '' ? ucwords($signerName) : 'Customer';
        $signingReference = substr(str_replace('-', '', (string) $token), 0, 12);
    @endphp

    <div id="success-state" class="success-card hidden">
        <span class="eyebrow" style="background:#eaddcf;">✓ completed</span>
        <h2 style="font-size: 2rem;">Gracefully signed</h2>
        <p>All documents received. Thank you for your signature.</p>
    </div>

    <div id="page-root" class="page">
        <div class="packet">
            <div class="document-list">
                @foreach($documents as $document)
                    @php
                        $sourceUrl = route('document.sign.file', ['token' => $token, 'document' => $document->id]);
                        $previewUrl = route('document.sign.preview', ['token' => $token, 'document' => $document->id]);
                        $extension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));
                        $signatureFields = collect($document->fields ?: [])->filter(function ($field) {
                            return is_array($field) && ($field['type'] ?? null) === 'signature';
                        })->values();
                    @endphp

                    <article class="document-block" id="document-{{ $document->id }}">
                        <div class="document-inner">
                            <div class="document-head">
                                <h2>{{ $document->document_name ?: 'Confidential document' }}</h2>
                            </div>

                            <div class="preview-surface">
                                @if(in_array($extension, ['pdf', 'doc', 'docx']))
                                    <div class="pdf-renderer" data-pdf-url="{{ $previewUrl }}" data-open-url="{{ $sourceUrl }}" data-document-id="{{ $document->id }}" data-fields='@json($document->fields ?: [])'>
                                        <div class="loading-card">📄 Loading preview ...</div>
                                    </div>
                                @elseif(in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp']))
                                    <div class="image-renderer" data-document-id="{{ $document->id }}" data-fields='@json($document->fields ?: [])'>
                                        <div class="image-page">
                                            <img src="{{ $sourceUrl }}" alt="{{ $document->document_name ?: 'Document preview' }}" style="width:100%; border-radius:1rem;">
                                        </div>
                                    </div>
                                @else
                                    <div class="fallback-renderer" style="padding:2rem; text-align:center;">
                                        <span>📎 Preview not available</span>
                                        <a href="{{ $sourceUrl }}" target="_blank" style="display:inline-block; margin-top:8px; color:#b68b40;">Open document →</a>
                                    </div>
                                @endif
                            </div>

                            @if($document->signature_required && $signatureFields->isEmpty())
                                <div class="signature-panel" data-sign-panel="{{ $document->id }}">
                                    <div class="signature-card">
                                        <p class="signature-title">Signature: {{ $signerName }}</p>

                                        <div class="signature-row">
                                            <div class="signature-row-label">Signature</div>
                                            <div class="signature-action signed-submit-row">
                                                <div class="signature-preview signature-trigger"
                                                     data-document-id="{{ $document->id }}"
                                                     data-signature-preview="{{ $document->id }}"
                                                     role="button"
                                                     tabindex="0">
                                                    <span class="signed-badge">
                                                        <span class="signed-badge-label">Signed by:</span>
                                                        <span class="signature-preview-label">Add your mark</span>
                                                        <span class="signed-badge-code">pending signature</span>
                                                    </span>
                                                </div>
                                                <div class="signed-submit-date" data-signature-date="{{ $document->id }}">—</div>
                                            </div>
                                        </div>

                                        <div class="signature-meta" data-signature-meta="{{ $document->id }}"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="submit-bar">
                <button id="submit-all" type="button" class="button button-primary">
                     {{ $documentsWithSignature > 0 ? 'Submit Signed ' : 'Confirm review' }}
                </button>
            </div>
        </div>
    </div>

    @if($documentsWithSignature > 0)
        <div class="signature-instructions" style="max-width: 980px; margin: 2rem auto; text-align:center; color:#7f7264; font-size:0.85rem;">
            <p>To complete the signing process, please ensure that you have added your signature to all required documents. Once all signatures are provided, click the "Submit Signed" button above to finalize your submission.</p>
        </div>
    @endif

    <div id="signature-modal" class="modal" aria-hidden="true">
        <div class="modal-card">
            <h3 id="modal-title">Add your signature</h3>
            <p style="color: #7f6e5b; margin-bottom: 1rem;">Choose name, initials, or draw your signature.</p>
            <div class="signature-mode-toggle" role="group" aria-label="Signature type">
                <button type="button" class="signature-mode-btn is-active" data-signature-mode="name">Name</button>
                <button type="button" class="signature-mode-btn" data-signature-mode="initial">Initial</button>
                <button type="button" class="signature-mode-btn" data-signature-mode="draw">Draw</button>
            </div>
            <canvas id="signature-pad" class="signature-canvas"></canvas>
            <div class="modal-actions">
                <button id="close-modal" type="button" class="button">Cancel</button>
                <button id="clear-signature" type="button" class="button">Clear</button>
                <button id="save-signature" type="button" class="button button-primary">Confirm</button>
            </div>
        </div>
    </div>

    <!-- SECOND MODAL (Message from Contracts) -->
    <div id="modalOverlay" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="brand">
                 <div class="">
            <img src="{{ asset('/assets/img/favicon.png') }}" alt="icon">
        </div>                      
            <span>Estimater</span>
                    </div>
                    {{-- <button class="close-btn" id="closeModalBtn" aria-label="Close modal">✕</button> --}}
                </div>
                <div class="title-section">
                    <div class="main-title">
                        Review and continue
                        <span class="badge-contracts">Contracts</span>
                    </div>
                    <div class="subtitle">
                        Message from Contracts EZ Estimater 
                    </div>
                </div>
                <div class="message-body">
                    <div class="greeting">Hi,</div>
                    <div class="message-text">
                        We have prepared and attached the Amended Service Order between {{ $signerName }} and EZ Estimater for your signature. 
                        Please review and sign the document at your earliest convenience. <br>Thanks!
                    </div>
                    {{-- <div class="attachment-badge">
                        Amended_Service_Order_v2.pdf
                    </div> --}}
                    {{-- <div class="signature-block">
                        <div class="avatar">JV</div>
                        <div class="signature-info">
                            <h4></h4>
                            <div class="email"></div>
                        </div>
                    </div> --}}
                </div>
                <div class="modal-actions">
                    <div class="dropdown-group">
                        {{-- <div class="custom-dropdown" id="langDropdown">
                            <button class="dropdown-btn" id="langBtn">
                                <span id="selectedLangText">English (US)</span>
                                <span class="chevron">▼</span>
                            </button>
                            <ul class="dropdown-menu" id="langMenu"> --}}
                                {{-- <li data-value="English (US)">English (US)</li> --}}
                                {{-- <li data-value="Spanish (ES)">Spanish (ES)</li>
                                <li data-value="French (FR)">French (FR)</li>
                                <li data-value="German (DE)">German (DE)</li> --}}
                            {{-- </ul>
                        </div> --}}
                        {{-- <div class="custom-dropdown" id="optionsDropdown">
                            <button class="dropdown-btn" id="optionsBtn">
                                <span id="selectedOptionText">Other Options</span>
                                <span class="chevron">▼</span>
                            </button>
                            <ul class="dropdown-menu" id="optionsMenu"> --}}
                                {{-- <li data-value="Download PDF">📄 Download PDF</li>
                                <li data-value="Forward message">↪️ Forward message</li>
                                <li data-value="Mark as unread">🔖 Mark as unread</li>
                                <li data-value="Archive">🗄️ Archive</li> --}}
                            {{-- </ul>
                        </div> --}}
                    </div>
                    <button class="continue-btn" id="closeModalBtn">Continue →</button>
                </div>
            </div>
        </div>
    </div>

    {{-- <button id="reopenModalBtn" class="reopen-trigger" style="display: none;">🔁 Reopen dialog</button> --}}

    <script>
        if (window.pdfjsLib) {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }

        const requiredDocumentIds = @json($requiredDocumentIds);
        const signatures = {};
        const signatureModes = {};
        const defaultSignatureCache = {};
        const defaultInitialCache = {};
        const fieldValues = {};
        const defaultSignerName = @json($signerName);
        const signingReference = @json($signingReference);
        const prefilledUserSignature = {
            url: @json($prefilledUserSignatureUrl ?? null),
            text: @json($prefilledUserSignatureText ?? null),
        };
        const signatureModal = document.getElementById('signature-modal');
        const signatureCanvas = document.getElementById('signature-pad');
        const signatureModeButtons = document.querySelectorAll('[data-signature-mode]');
        const signaturePad = new SignaturePad(signatureCanvas, {
            minWidth: 1.2,
            maxWidth: 2.6,
            penColor: '#3a3530',
            backgroundColor: 'rgba(0,0,0,0)'
        });
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const submitButton = document.getElementById('submit-all');
        let activeDocumentId = null;
        let activeSignatureMode = 'name';

        function formatToday() {
            const date = new Date();
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        }

        function formatShortDate() {
            const date = new Date();
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'numeric', day: 'numeric' });
        }

        function signatureReference(documentId) {
            return `${signingReference}${String(documentId || '').padStart(3, '0')}`.slice(0, 14).toUpperCase();
        }

        function signedBadgeHtml(dataUrl, documentId) {
            return `
                <span class="signed-badge">
                    <span class="signed-badge-label">Signed by:</span>
                    <img src="${dataUrl}" alt="signature">
                    <span class="signed-badge-code">${signatureReference(documentId)}...</span>
                </span>
            `;
        }

        function initialsFromName(name) {
            const parts = String(name || 'Customer')
                .trim()
                .split(/\s+/)
                .filter(Boolean);

            if (!parts.length) return 'C';

            return parts.map(part => part.charAt(0)).join('').toUpperCase();
        }

        function buildDefaultSignatureDataUrl(name, initialsOnly = false) {
            const canvas = document.createElement('canvas');
            canvas.width = 900;
            canvas.height = 150;
            const ctx = canvas.getContext('2d');
            const safeName = initialsOnly ? initialsFromName(name) : (String(name || 'Customer').trim() || 'Customer');
            let fontSize = initialsOnly ? 104 : 94;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#3a3530';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            do {
                ctx.font = `italic ${fontSize}px "Brush Script MT", "Segoe Script", "Lucida Handwriting", cursive`;
                fontSize -= 4;
            } while (ctx.measureText(safeName).width > canvas.width - 60 && fontSize > 36);

            ctx.fillText(safeName, canvas.width / 2, 78);

            return canvas.toDataURL('image/png');
        }

        function defaultSignatureForDocument(documentId) {
            const key = String(documentId || 'default');
            if (!defaultSignatureCache[key]) {
                defaultSignatureCache[key] = buildDefaultSignatureDataUrl(defaultSignerName);
            }
            return defaultSignatureCache[key];
        }

        function defaultInitialForDocument(documentId) {
            const key = String(documentId || 'default');
            if (!defaultInitialCache[key]) {
                defaultInitialCache[key] = buildDefaultSignatureDataUrl(defaultSignerName, true);
            }
            return defaultInitialCache[key];
        }

        function updateSignatureModeUi() {
            signatureModeButtons.forEach(button => {
                const isActive = button.dataset.signatureMode === activeSignatureMode;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
            signatureCanvas.classList.toggle('is-name-mode', activeSignatureMode === 'name' || activeSignatureMode === 'initial');
            signatureCanvas.style.pointerEvents = activeSignatureMode === 'draw' ? 'auto' : 'none';
        }

        function paintSignatureToCanvas(dataUrl, markPad = true) {
            const rect = signatureCanvas.getBoundingClientRect();
            const ctx = signatureCanvas.getContext('2d');
            ctx.clearRect(0, 0, rect.width, rect.height);
            signaturePad.clear();
            if (!dataUrl) return;
            const img = new Image();
            img.onload = () => {
                ctx.clearRect(0, 0, rect.width, rect.height);
                ctx.drawImage(img, 0, 0, rect.width, rect.height);
                if (markPad) signaturePad.fromDataURL(dataUrl);
            };
            img.src = dataUrl;
        }

        function setSignatureMode(mode, resetDraw = false) {
            activeSignatureMode = ['name', 'initial', 'draw'].includes(mode) ? mode : 'name';
            updateSignatureModeUi();
            if (activeSignatureMode === 'name') {
                paintSignatureToCanvas(defaultSignatureForDocument(activeDocumentId));
                return;
            }
            if (activeSignatureMode === 'initial') {
                paintSignatureToCanvas(defaultInitialForDocument(activeDocumentId));
                return;
            }
            if (resetDraw) {
                signaturePad.clear();
                return;
            }
            paintSignatureToCanvas(signatureModes[activeDocumentId] === 'draw' ? signatures[activeDocumentId] : null);
        }
        function resizeCanvasAndRestore() {
            const rect = signatureCanvas.getBoundingClientRect();
            const ratio = window.devicePixelRatio || 1;
            signatureCanvas.width = rect.width * ratio;
            signatureCanvas.height = rect.height * ratio;
            const ctx = signatureCanvas.getContext('2d');
            ctx.scale(ratio, ratio);
            setSignatureMode(activeSignatureMode);
        }

        function exportDataUrl() {
            const originalCanvas = signatureCanvas;
            const copyCanvas = document.createElement('canvas');
            copyCanvas.width = originalCanvas.width;
            copyCanvas.height = originalCanvas.height;
            const copyCtx = copyCanvas.getContext('2d');
            copyCtx.clearRect(0, 0, copyCanvas.width, copyCanvas.height);
            copyCtx.drawImage(originalCanvas, 0, 0);
            return copyCanvas.toDataURL('image/png');
        }

        function openSignatureModal(documentId) {
            activeDocumentId = String(documentId);
            activeSignatureMode = signatureModes[activeDocumentId] || 'name';
            const panel = document.querySelector(`[data-sign-panel="${activeDocumentId}"]`);
            const titleEl = panel?.querySelector('.signature-title');
            document.getElementById('modal-title').innerText = titleEl ? titleEl.innerText : 'Sign document';
            signatureModal.classList.add('is-open');
            document.body.classList.add('modal-open');
            requestAnimationFrame(() => {
                resizeCanvasAndRestore();
            });
        }

        function closeModal() {
            signatureModal.classList.remove('is-open');
            document.body.classList.remove('modal-open');
            activeDocumentId = null;
            signaturePad.clear();
        }

        function updatePreview(documentId, dataUrl) {
            const previewDiv = document.querySelector(`[data-signature-preview="${documentId}"]`);
            const metaSpan = document.querySelector(`[data-signature-meta="${documentId}"]`);
            const dateSpan = document.querySelector(`[data-signature-date="${documentId}"]`);
            const todayStr = formatToday();

            if (previewDiv) {
                previewDiv.classList.add('is-signed');
                previewDiv.innerHTML = signedBadgeHtml(dataUrl, documentId);
                previewDiv.title = 'Tap to edit signature';
            }
            if (metaSpan) {
                metaSpan.innerText = 'Signature secured, ready for submission.';
            }
            if (dateSpan) {
                dateSpan.innerText = formatShortDate();
            }
        }

        document.querySelectorAll('.signature-trigger').forEach(trigger => {
            trigger.addEventListener('click', () => openSignatureModal(trigger.dataset.documentId));
            trigger.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openSignatureModal(trigger.dataset.documentId); } });
        });

        document.getElementById('close-modal').addEventListener('click', closeModal);
        signatureModeButtons.forEach(button => {
            button.addEventListener('click', () => setSignatureMode(button.dataset.signatureMode, button.dataset.signatureMode === 'draw'));
        });

        document.getElementById('clear-signature').addEventListener('click', () => {
            if (activeSignatureMode === 'name' || activeSignatureMode === 'initial') {
                setSignatureMode(activeSignatureMode);
                return;
            }
            signaturePad.clear();
        });
        document.getElementById('save-signature').addEventListener('click', () => {
            if (!activeDocumentId) return;
            if (activeSignatureMode === 'name') {
                signatures[activeDocumentId] = defaultSignatureForDocument(activeDocumentId);
                signatureModes[activeDocumentId] = 'name';
            } else if (activeSignatureMode === 'initial') {
                signatures[activeDocumentId] = defaultInitialForDocument(activeDocumentId);
                signatureModes[activeDocumentId] = 'initial';
            } else {
                if (signaturePad.isEmpty()) {
                    alert('Please draw your signature before saving.');
                    return;
                }
                signatures[activeDocumentId] = exportDataUrl();
                signatureModes[activeDocumentId] = 'draw';
            }
            updatePreview(activeDocumentId, signatures[activeDocumentId]);
            renderAllFieldOverlays();
            closeModal();
        });

        signatureModal.addEventListener('click', (e) => { if (e.target === signatureModal) closeModal(); });
        window.addEventListener('resize', () => {
            if (signatureModal.classList.contains('is-open')) requestAnimationFrame(resizeCanvasAndRestore);
            requestAnimationFrame(renderAllFieldOverlays);
        });

        function parseRendererFields(container) {
            try {
                const fields = JSON.parse(container.dataset.fields || '[]');
                return Array.isArray(fields) ? fields : [];
            } catch (err) {
                return [];
            }
        }

        function resolveFieldRect(field, pageWidth, pageHeight) {
            let x = Number(field.x ?? field.left ?? 0);
            let y = Number(field.y ?? field.top ?? 0);
            let width = Number(field.width ?? field.w ?? 40);
            let height = Number(field.height ?? field.h ?? 10);
            const unit = String(field.unit || 'mm').toLowerCase();
            const baseWidth = Number(field.page_width || 0);
            const baseHeight = Number(field.page_height || 0);

            if (unit === 'percent' || unit === 'percentage' || unit === 'ratio' || (x <= 1 && y <= 1 && width <= 1 && height <= 1)) {
                x *= pageWidth;
                width *= pageWidth;
                y *= pageHeight;
                height *= pageHeight;
            } else if ((unit === 'px' || unit === 'pixel' || unit === 'pixels') && baseWidth > 0 && baseHeight > 0) {
                x = (x / baseWidth) * pageWidth;
                width = (width / baseWidth) * pageWidth;
                y = (y / baseHeight) * pageHeight;
                height = (height / baseHeight) * pageHeight;
            }

            return { x, y, width, height };
        }

        function normalizeFieldKey(field) {
            return String(field.key || field.name || field.label || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        }

        function isUserSignatureField(field) {
            return ['esign_user', 'user_signature', 'contractor_signature'].includes(normalizeFieldKey(field));
        }

        function fieldLooksLikeSignature(field) {
            const value = String((field.type || '') + ' ' + (field.key || '') + ' ' + (field.label || '')).toLowerCase();
            return value.includes('signature') || value.includes('e-signature') || value.includes('esignature') || value.includes('esign_customer') || value.includes('esign_user') || value.includes('esign');
        }

        function fieldIdentity(field) {
            return String(field.id || field.key || field.label || 'field');
        }

        function addFieldOverlay(pageEl, field, documentId) {
            const pageRect = pageEl.getBoundingClientRect();
            if (!pageRect.width || !pageRect.height) return;

            const fullPageHeight = pageEl.dataset.fullPageRatio
                ? pageEl.clientWidth * Number(pageEl.dataset.fullPageRatio)
                : pageEl.clientHeight;
            const cropTop = fullPageHeight * Number(pageEl.dataset.cropTopRatio || 0);
            const rect = resolveFieldRect(field, pageEl.clientWidth, fullPageHeight);
            const overlay = document.createElement('div');
            const isSignature = fieldLooksLikeSignature(field);
            const fieldId = fieldIdentity(field);
            overlay.className = `field-overlay${isSignature ? ' is-signature' : ' is-editable'}`;
            overlay.style.left = `${Math.max(0, rect.x)}px`;
            overlay.style.top = `${Math.max(0, rect.y - cropTop)}px`;
            overlay.style.width = `${Math.max(1, rect.width)}px`;
            overlay.style.height = `${Math.max(1, rect.height)}px`;
            overlay.style.fontSize = Math.max(7, Math.min(20, rect.height * 0.45)) + 'px';

            if (isSignature) {
                if (isUserSignatureField(field)) {
                    if (prefilledUserSignature.url) {
                        overlay.innerHTML = signedBadgeHtml(prefilledUserSignature.url, documentId);
                    } else {
                        overlay.textContent = prefilledUserSignature.text || field.value || field.label || field.key || '';
                    }
                    overlay.title = 'User signature';
                } else {
                    const signatureImage = signatures[String(documentId)];
                    if (signatureImage) {
                        overlay.innerHTML = signedBadgeHtml(signatureImage, documentId);
                    } else {
                        overlay.textContent = field.label || field.key || field.type || '';
                    }
                    overlay.title = 'Click to sign';
                    overlay.addEventListener('click', () => openSignatureModal(documentId));
                }
            } else {
                const fieldType = String(field.type || '').toLowerCase();
                const input = document.createElement(fieldType === 'custom_integer' ? 'input' : 'textarea');
                input.className = 'field-overlay-input';
                if (fieldType === 'custom_integer') {
                    input.type = 'number';
                    input.step = '1';
                } else {
                    input.rows = 2;
                    input.wrap = 'soft';
                }
                input.dataset.fieldId = fieldId;
                input.dataset.fieldKey = field.key || '';
                input.placeholder = fieldType === 'custom_string'
                    ? 'Custom String'
                    : (fieldType === 'custom_integer' ? 'Custom Integer' : (field.label || field.key || ''));
                input.value = fieldValues?.[documentId]?.[fieldId] ?? field.value ?? '';
                input.addEventListener('input', () => {
                    if (!fieldValues[documentId]) fieldValues[documentId] = {};
                    fieldValues[documentId][fieldId] = input.value;
                    if (field.key) fieldValues[documentId][field.key] = input.value;
                });
                overlay.appendChild(input);
            }

            pageEl.appendChild(overlay);
        }

        function renderFieldOverlaysForContainer(container) {
            const fields = parseRendererFields(container);
            if (!fields.length) return;

            const documentId = container.dataset.documentId;
            container.querySelectorAll('.field-overlay-input').forEach(input => {
                const fieldId = input.dataset.fieldId;
                const key = input.dataset.fieldKey;
                if (!fieldId) return;
                if (!fieldValues[documentId]) fieldValues[documentId] = {};
                fieldValues[documentId][fieldId] = input.value;
                if (key) fieldValues[documentId][key] = input.value;
            });
            container.querySelectorAll('.field-overlay').forEach(el => el.remove());

            fields.forEach(field => {
                const pageNumber = Number(field.page || field.page_number || 1);
                const pageEl = container.querySelector(`[data-page-number="${pageNumber}"]`);
                if (pageEl) addFieldOverlay(pageEl, field, documentId);
            });
        }

        function renderAllFieldOverlays() {
            document.querySelectorAll('.pdf-renderer, .image-renderer').forEach(renderFieldOverlaysForContainer);
        }

        function trimPdfPage(wrapper, canvas, scaled, fields) {
            const sampleWidth = Math.min(240, canvas.width);
            const sampleHeight = Math.max(1, Math.round(canvas.height * sampleWidth / canvas.width));
            const sample = document.createElement('canvas');
            sample.width = sampleWidth;
            sample.height = sampleHeight;
            const sampleContext = sample.getContext('2d', { willReadFrequently: true });
            sampleContext.drawImage(canvas, 0, 0, sampleWidth, sampleHeight);
            const pixels = sampleContext.getImageData(0, 0, sampleWidth, sampleHeight).data;
            let firstRow = sampleHeight;
            let lastRow = -1;

            for (let y = 0; y < sampleHeight; y++) {
                for (let x = 0; x < sampleWidth; x++) {
                    const index = (y * sampleWidth + x) * 4;
                    if (pixels[index + 3] > 10 && (pixels[index] < 248 || pixels[index + 1] < 248 || pixels[index + 2] < 248)) {
                        firstRow = Math.min(firstRow, y);
                        lastRow = Math.max(lastRow, y);
                        break;
                    }
                }
            }

            if (lastRow < firstRow) return;

            const padding = 10;
            let cropTop = Math.max(0, (firstRow / sampleHeight) * scaled.height - padding);
            let cropBottom = Math.min(scaled.height, ((lastRow + 1) / sampleHeight) * scaled.height + padding);

            fields.forEach(field => {
                const rect = resolveFieldRect(field, scaled.width, scaled.height);
                cropTop = Math.min(cropTop, Math.max(0, rect.y - padding));
                cropBottom = Math.max(cropBottom, Math.min(scaled.height, rect.y + rect.height + padding));
            });

            const croppedHeight = Math.max(1, cropBottom - cropTop);
            wrapper.dataset.fullPageRatio = String(scaled.height / scaled.width);
            wrapper.dataset.cropTopRatio = String(cropTop / scaled.height);
            wrapper.style.aspectRatio = `${scaled.width} / ${croppedHeight}`;
            canvas.style.position = 'absolute';
            canvas.style.left = '0';
            canvas.style.top = '0';
            canvas.style.transform = `translateY(-${(cropTop / scaled.height) * 100}%)`;
        }

        async function renderPdf(container) {
            const pdfUrl = container.dataset.pdfUrl;
            const openUrl = container.dataset.openUrl || pdfUrl;
            if (!window.pdfjsLib) {
                container.innerHTML = `<div class="error-card">Preview unavailable. <a href="${openUrl}" target="_blank" style="color:#b68b40;">Open document</a></div>`;
                return;
            }
            try {
                const pdf = await window.pdfjsLib.getDocument(pdfUrl).promise;
                const fields = parseRendererFields(container);
                container.innerHTML = '';
                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const viewport = page.getViewport({ scale: 1 });
                    const maxWidth = container.clientWidth || 1000;
                    const scale = maxWidth / viewport.width;
                    const scaled = page.getViewport({ scale });
                    const outputScale = Math.min((window.devicePixelRatio || 1) * 2, 4);
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.floor(scaled.width * outputScale);
                    canvas.height = Math.floor(scaled.height * outputScale);
                    canvas.style.width = '100%';
                    canvas.style.height = 'auto';
                    const wrapper = document.createElement('div');
                    wrapper.className = 'pdf-page';
                    wrapper.dataset.pageNumber = String(i);
                    wrapper.style.width = `${scaled.width}px`;
                    wrapper.style.maxWidth = '100%';
                    wrapper.style.margin = '0 auto';
                    wrapper.appendChild(canvas);
                    container.appendChild(wrapper);
                    const context = canvas.getContext('2d');
                    context.setTransform(outputScale, 0, 0, outputScale, 0, 0);
                    await page.render({ canvasContext: context, viewport: scaled }).promise;
                    trimPdfPage(wrapper, canvas, scaled, fields.filter(field => Number(field.page || field.page_number || 1) === i));
                }
                renderFieldOverlaysForContainer(container);
            } catch (err) {
                container.innerHTML = `<div class="error-card">⚠️ Could not load PDF. <a href="${openUrl}" target="_blank" style="color:#b68b40;">View document</a></div>`;
            }
        }

        document.querySelectorAll('.pdf-renderer').forEach(renderPdf);
        document.querySelectorAll('.image-renderer .image-page').forEach(page => {
            page.dataset.pageNumber = '1';
            const image = page.querySelector('img');
            const container = page.closest('.image-renderer');
            if (image && !image.complete) {
                image.addEventListener('load', () => renderFieldOverlaysForContainer(container), { once: true });
            } else if (container) {
                renderFieldOverlaysForContainer(container);
            }
        });

        async function submitFinal() {
            const missing = requiredDocumentIds.map(String).filter(id => !signatures[id]);
            if (missing.length) {
                const firstMissingEl = document.getElementById(`document-${missing[0]}`);
                if (firstMissingEl) firstMissingEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                alert('Please sign all required documents before final submission.');
                return;
            }
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting ...';
            try {
                const response = await fetch('/document/sign/{{ $token }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ signatures, field_values: fieldValues })
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Submission error');
                window.location.reload();
            } catch (err) {
                alert(err.message);
                submitButton.disabled = false;
                submitButton.textContent = 'Submit Signed';
            }
        }

        submitButton.addEventListener('click', submitFinal);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && signatureModal.classList.contains('is-open')) closeModal(); });

        // SECOND MODAL SCRIPT (independent)
        (function() {
            const modalOverlay = document.getElementById('modalOverlay');
            const closeModalBtn = document.getElementById('closeModalBtn');
            const reopenBtn = document.getElementById('reopenModalBtn');
            const continueBtn = document.getElementById('continueBtn');
            const langBtn = document.getElementById('langBtn');
            const langMenu = document.getElementById('langMenu');
            const selectedLangText = document.getElementById('selectedLangText');
            const optionsBtn = document.getElementById('optionsBtn');
            const optionsMenu = document.getElementById('optionsMenu');
            const selectedOptionText = document.getElementById('selectedOptionText');

            function closeAllDropdowns(except = null) {
                [langMenu, optionsMenu].forEach(menu => {
                    if (menu && (except !== menu)) menu.classList.remove('show');
                });
            }

            function toggleDropdown(menu, btn) {
                if (!menu) return;
                const isOpen = menu.classList.contains('show');
                closeAllDropdowns();
                if (!isOpen) menu.classList.add('show');
            }

            if (langBtn && langMenu) {
                langBtn.addEventListener('click', (e) => { e.stopPropagation(); toggleDropdown(langMenu, langBtn); });
                langMenu.querySelectorAll('li').forEach(item => {
                    item.addEventListener('click', (e) => {
                        e.stopPropagation();
                        selectedLangText.innerText = item.getAttribute('data-value') || item.innerText;
                        langMenu.classList.remove('show');
                        alert(`🌐 Language preference updated to "${selectedLangText.innerText}". (Demo interaction)`);
                    });
                });
            }
            if (optionsBtn && optionsMenu) {
                optionsBtn.addEventListener('click', (e) => { e.stopPropagation(); toggleDropdown(optionsMenu, optionsBtn); });
                optionsMenu.querySelectorAll('li').forEach(item => {
                    item.addEventListener('click', (e) => {
                        e.stopPropagation();
                        let val = item.getAttribute('data-value') || item.innerText;
                        val = val.replace(/[📄↪️🔖🗄️]/g, '').trim();
                        selectedOptionText.innerText = val || "Other Options";
                        optionsMenu.classList.remove('show');
                        alert(`🔘 Other Options: "${val}" selected. (Demo action)`);
                    });
                });
            }
            document.addEventListener('click', (e) => {
                const isLang = langBtn?.contains(e.target) || langMenu?.contains(e.target);
                const isOpt = optionsBtn?.contains(e.target) || optionsMenu?.contains(e.target);
                if (!isLang && !isOpt) closeAllDropdowns();
            });
            if (langMenu) langMenu.addEventListener('click', e => e.stopPropagation());
            if (optionsMenu) optionsMenu.addEventListener('click', e => e.stopPropagation());

            function closeModalOverlay() {
                if (modalOverlay) {
                    modalOverlay.classList.add('hidden-modal');
                    modalOverlay.style.display = 'none';
                    if (reopenBtn) reopenBtn.style.display = 'flex';
                }
                closeAllDropdowns();
            }
            function openModalOverlay() {
                if (modalOverlay) {
                    modalOverlay.classList.remove('hidden-modal');
                    modalOverlay.style.display = 'flex';
                    if (reopenBtn) reopenBtn.style.display = 'none';
                    closeAllDropdowns();
                }
            }
            if (closeModalBtn) closeModalBtn.addEventListener('click', closeModalOverlay);
            if (modalOverlay) modalOverlay.addEventListener('click', (e) => { if (e.target === modalOverlay) closeModalOverlay(); });
            if (reopenBtn) reopenBtn.addEventListener('click', openModalOverlay);
            if (continueBtn) continueBtn.addEventListener('click', (e) => {
                e.preventDefault();
                alert("✅ Thank you! The Amended Service Order will be reviewed.\n(Your signature request has been noted — demo flow)");
                continueBtn.style.transform = "scale(0.97)";
                setTimeout(() => { if(continueBtn) continueBtn.style.transform = ""; }, 120);
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modalOverlay && modalOverlay.style.display !== 'none') closeModalOverlay();
            });
            if (modalOverlay) {
                modalOverlay.style.display = 'flex';
                modalOverlay.classList.remove('hidden-modal');
                if (reopenBtn) reopenBtn.style.display = 'none';
            }
            closeAllDropdowns();
        })();
    </script>
</body>
</html>
