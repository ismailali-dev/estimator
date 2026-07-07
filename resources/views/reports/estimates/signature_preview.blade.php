<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate</title>
    <style>
        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 14px;
            color: #000;
            background: #fff;
        }
        .estimate-page {
            width: 700px;
            min-height: 980px;
            margin: 0 auto;
            padding: 18px 46px 24px;
            box-sizing: border-box;
            position: relative;
            background: #fff;
        }
        .company-header {
            text-align: center;
            height: 132px;
            overflow: hidden;
        }
        .company-logo {
            display: inline-block;
            width: 84px;
            height: 84px;
            vertical-align: top;
            margin: 0 8px 0 0;
        }
        .company-logo img {
            width: 84px;
            height: 84px;
            object-fit: contain;
        }
        .company-info {
            display: inline-block;
            vertical-align: top;
            text-align: left;
            line-height: 1.18;
            font-size: 21px;
        }
        .company-info h1 {
            margin: 0;
            padding: 0;
            font-size: 27px;
            line-height: 1.05;
            font-weight: bold;
        }
        .estimate-title {
            margin: 0 0 14px;
            text-align: center;
            font-size: 27px;
            line-height: 1;
            font-weight: bold;
        }
        .quote-line {
            clear: both;
            height: 30px;
            font-size: 14px;
        }
        .quote-line .left {
            float: left;
            width: 48%;
        }
        .quote-line .right {
            float: right;
            width: 42%;
            text-align: right;
            padding-right: 26px;
            box-sizing: border-box;
        }
        .details {
            clear: both;
            min-height: 112px;
        }
        .details .left {
            float: left;
            width: 56%;
        }
        .details .right {
            float: right;
            width: 36%;
            text-align: right;
            padding-right: 26px;
            box-sizing: border-box;
        }
        h2 {
            margin: 0 0 12px;
            padding: 0;
            font-size: 18px;
            line-height: 1;
            font-weight: bold;
        }
        p {
            margin: 0 0 10px;
            padding: 0;
        }
        .section {
            clear: both;
            margin-top: 4px;
        }
        .scope-section {
            margin-top: 10px;
        }
        .specifications {
            margin-top: 10px;
        }
        .group-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 8px;
        }
        .group-table td {
            border: 1px solid #000;
            color: #fff;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            padding: 9px 8px;
            line-height: 1;
            text-transform: uppercase;
        }
        .spec-row {
            display: table;
            width: 100%;
            table-layout: fixed;
            clear: both;
            line-height: 1.35;
            margin: 4px 0;
        }
        .spec-no,
        .spec-desc,
        .spec-qty,
        .spec-unit {
            display: table-cell;
            vertical-align: top;
        }
        .spec-no {
            width: 36px;
        }
        .spec-desc {
            width: auto;
            padding-right: 12px;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }
        .spec-qty {
            width: 78px;
            text-align: center;
        }
        .spec-unit {
            width: 54px;
            text-align: center;
        }
        .total-row {
            margin-top: 36px;
            text-align: right;
            white-space: nowrap;
            font-size: 15px;
        }
        .total-row .label {
            display: inline-block;
            font-weight: bold;
            margin-right: 24px;
        }
        .total-row .amount {
            display: inline-block;
            min-width: 130px;
            text-align: left;
            font-size: 23px;
            font-weight: bold;
        }
        .signature-section {
            clear: both;
            margin-top: 72px;
            text-align: center;
        }
        .signature-column {
            float: left;
            width: 25%;
            font-weight: bold;
        }
        .signature-value {
            height: 42px;
            line-height: 42px;
            font-weight: normal;
        }
        .signature-value img {
            max-height: 68px;
            max-width: 135px;
            vertical-align: bottom;
        }
    </style>
</head>
<body>
<div class="estimate-page">
    <div class="company-header">
        <div class="company-logo">
            <img src="{{ $logoPath }}">
        </div>
        <div class="company-info">
            <h1>{{ @$company->name }}</h1>
            <div>{{ @$company->address }}</div>
            <div>{{ @$user->phone }}</div>
            <div>{{ @$user->email }}</div>
        </div>
    </div>

    <div class="estimate-title">Estimate</div>

    <div class="quote-line">
        <div class="left"><strong>Estimate No:</strong> {{ $estimate->key }}</div>
        <div class="right"><strong>Estimate Date:</strong> {{ $estimate->created_at->format('M j, Y') }}</div>
    </div>

    <div class="details">
        <div class="left">
            <h2>Customer Details:</h2>
            <p><strong>Name:</strong> {{ @$customer->name }}</p>
            <p><strong>Phone:</strong> {{ @$customer->phone }}</p>
            <p><strong>Address:</strong> {{ @$customer->address }}</p>
        </div>
        <div class="right">
            <h2>Quote Details:</h2>
            <p><strong>Estimate Type:</strong> {{ @$estimate->estimateType->name }}</p>
            <p><strong>Quoted By:</strong> {{ @$user->first_name . ' ' . @$user->last_name }}</p>
        </div>
    </div>

    <div class="section scope-section">
        <h2>Scope of Work:</h2>
        <p>{{ $estimate->estimate_scope }}</p>
    </div>

    <div class="section specifications">
        <h2>Specifications:</h2>
        @php
            $currentProductGroup = null;
            $sno = 0;
            $groupBackgroundColor = '#' . ltrim($color ?: '000000', '#');
        @endphp
        @foreach ($sheets as $sheet)
            @php
                $productGroup = optional($sheet->code->ProductGroup)->group_name;
            @endphp
            @if ($currentProductGroup !== $productGroup)
                @php
                    $currentProductGroup = $productGroup;
                    $sno = 0;
                @endphp
                <table class="group-table" cellspacing="0" cellpadding="0"><tr><td bgcolor="{{ $groupBackgroundColor }}" style="background-color: {{ $groupBackgroundColor }}; color: #fff; border: 1px solid #000; font-size: 16px;">{{ $currentProductGroup }}</td></tr></table>
            @endif
            @php $sno++; @endphp
            <div class="spec-row">
                <span class="spec-no">{{ $sno }}.</span>
                <span class="spec-desc">{{ optional($sheet->code)->description }}</span>
                <span class="spec-qty">{{ optional($sheet)->quantity }}</span>
                <span class="spec-unit">{{ optional($sheet)->unit }}</span>
            </div>
        @endforeach
    </div>

    <div class="total-row">
        <span class="label">Grand Total w/tax:</span>
        <span class="amount">${{ $grand_total }}</span>
    </div>

    <div class="signature-section">
        <div class="signature-column">
            <div class="signature-value">
                @if(isset($signatureData['customer_signature']) && !empty($signatureData['customer_signature']))
                    <img src="{{ $signatureData['customer_signature'] }}" alt="Customer Signature">
                @endif
            </div>
            Customer Signature
        </div>
        <div class="signature-column">
            <div class="signature-value">{{ @$signatureData['customer_signed_at'] ? \Carbon\Carbon::parse($signatureData['customer_signed_at'])->format('m/d/Y') : '' }}</div>
            Date
        </div>
        <div class="signature-column">
            <div class="signature-value">
                @if(isset($signatureData['estimator_signature']) && !empty($signatureData['estimator_signature']))
                    <img src="{{ $signatureData['estimator_signature'] }}" alt="Estimator Signature">
                @endif
            </div>
            {{ @$company->name }}
        </div>
        <div class="signature-column">
            <div class="signature-value">{{ @$signatureData['estimator_signed_at'] ? \Carbon\Carbon::parse($signatureData['estimator_signed_at'])->format('m/d/Y') : '' }}</div>
            Date
        </div>
    </div>
</div>
</body>
</html>
