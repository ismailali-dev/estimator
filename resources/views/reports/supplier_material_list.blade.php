<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Material List</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            text-align: center;
            margin: 0;
            padding: 0;
        }
        .company-details {
            display: table;
            margin: 0 auto;
            margin-bottom: 20px;
        }
        .company-logo {
            width: 100px;
            height: auto;
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
        }
        table, th, td {
            border: 1px solid #fff;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        table td {
            font-size: 13px;
        }
        .sales-report-title {
            margin-bottom: 10px;
        }
        .category {
            font-weight: bold;
            font-size: 13px;
        }
        @page { margin: 20px 20px; }
        footer {
            position: fixed;
            bottom: -10px;
            left: 0; right: 0;
        }
        footer p{
            text-align: center;
                font-size: 10pt;
                color: gray;
        }
    </style>
</head>
<body>
<footer>@php
        if ($user->hasSubscription('invoice')) {
            $footerText = ''; // No footer text for subscribed users
        } else {
            $footerText = "This estimate was generated using EZ-Estimater app on " . date('F j, Y');
        }
    @endphp
    <p class="footer-text">{{ $footerText }}</p>
    
</footer>
<div class="company-details">
    <div class="company-logo">
        <img src="{{ $logoPath }}" alt="Company Logo" width="100%">
    </div>
    <div class="company-info">
        <h2 style="margin:0px">{{ $company['name'] }}</h2>
        <p style="margin:0px">{{ $company['address'] }}<br>
           {{ $company['phone'] }}<br>{{ $company['email'] }}</p>
    </div>
</div>

<div class="sales-report-title">
    <h3>Supplier Material List</h3>
</div>

@php
    // Helper function to determine if a color is dark or light
    function isDarkColor($color) {
        $color = str_replace('#', '', $color);
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        return $brightness < 128; 
    }

    $backgroundColor = $user->hasSubscription('invoice') ? '#'.$color : '#ffffff';
    $textColor = isDarkColor($backgroundColor) ? '#ffffff' : '#000000';
@endphp

<table>
    <thead>
        <tr>
            <th>Qty</th>
            <th>Unit</th>
            <th>Sku</th>
            <th>Model</th>
            <th style="width: 65%;text-align:center">Description</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($groupCodes as $category => $items)
            <tr @if($user->hasSubscription('invoice')) style="background-color: {{ $backgroundColor }}; color: {{ $textColor }};" @endif>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="category" style="text-align:center">{{ $category }}</td>
            </tr>

            @foreach ($items as $item)
                <tr>
                    <td style="text-align:center">{{ $item->quantity }}</td>
                    <td style="text-align:center">{{ $item->unit }}</td>
                    <td style="text-align:center">{{ $item->Code->sku_no }}</td>
                    <td style="text-align:center">{{ $item->Code->model_no }}</td>
                    <td class="description">{{ $item->Code->description }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>


</body>
</html>
