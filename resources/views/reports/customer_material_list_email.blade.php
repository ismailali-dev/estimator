<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Material List</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            text-align: center;
            margin: 0;
        }
        .company-details {
            display: table;
            margin: 0 auto;
            margin-bottom: 20px;
        }
        .company-logo {
            width: 100px;
            display: table-cell;
            vertical-align: middle;
        }
        .company-logo img {
            display: block;
            width: 100px;
            max-width: 100px;
            max-height: 100px;
            height: auto;
            object-fit: contain;
        }
        .company-info {
            display: table-cell;
            vertical-align: middle;
            padding-left: 10px;
            text-align: left;
        }
        h2, h3 {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            page-break-inside: auto;
        }
        table, th, td {
            border: 1px solid #fff;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        thead {
            display: table-header-group;
        }
        tfoot {
            display: table-footer-group;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        table td {
            font-size: 13px;
        }
        .sales-report-title {
            margin-bottom: 10px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10pt;
            color: gray;
            padding: 10px 0;
        }
        .category {
            font-weight: bold;
            font-size: 13px;
        }
        @page { margin: 24px 20px 48px; }
        footer {
            position: fixed;
            bottom: -32px;
            left: 0; right: 0;
        }
        footer p {
            text-align: center;
            font-size: 10pt;
            color: gray;
            margin: 0;
        }
        .material-list-header {
            page-break-inside: avoid;
        }
        .category-row {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
<footer>
    @php
        if ($user->hasSubscription('invoice')) {
            $footerText = '';
        } else {
            $footerText = " "   ;
        }
    @endphp
    <p class="footer-text">{{ $footerText }}</p>
</footer>

<div class="material-list-header">
    <div class="company-details">
        <div class="company-logo">
            <img src="{{ $logoPath }}" alt="Company Logo">
        </div>
        <div class="company-info">
            <h2 style="margin:0px">{{ $company['name'] }}</h2>
            <p style="margin:0px">{{ $company['address'] }}<br>
            {{ $company['phone'] }}
            <br>{{ $company['email'] }}</p>
        </div>
    </div>

    <div class="sales-report-title">
        <h3>Customer Material List</h3>
    </div>
</div>

@php
    $isDarkEmailMaterialListColor = function ($color) {
        $color = str_replace('#', '', $color);
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        return $brightness < 128;
    };

    $backgroundColor = $user->hasSubscription('invoice') ? '#'.$color : '#ffffff';
    $textColor = $isDarkEmailMaterialListColor($backgroundColor) ? '#ffffff' : '#000000';
@endphp

<table>
    <thead>
        <tr>
            <th>Qty</th>
            <th>Unit</th>
            <th>Sku</th>
            <th>Model</th>
            <th style="width: 50%; text-align:center">Description</th>
            <th>Cost</th>
            <th style="width:15%">Total Cost</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($groupCodes as $category => $items)
            <tr class="category-row" @if($user->hasSubscription('invoice')) style="background-color: {{ $backgroundColor }}; color: {{ $textColor }};" @endif>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="category" style="text-align:center">{{ $category }}</td>
                <td></td>
                <td></td>
            </tr>

            @foreach ($items as $item)
                <tr>
                    <td style="text-align:right">{{ $item->quantity }}</td>
                    <td style="text-align:center">{{ $item->unit }}</td>
                    <td style="text-align:center">{{ $item->Code->sku_no }}</td>
                    <td style="text-align:center">{{ $item->Code->model_no }}</td>
                    <td class="description">{{ $item->Code->description }}</td>
                    <td style="width:10%; text-align:right">
                        ${{ number_format($item->total_material_cost, 2) }}
                    </td>
                    <td style="width:10%; text-align:right">
                        ${{ number_format($item->total_actual_material_cost, 2) }}
                    </td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
</body>
</html>
