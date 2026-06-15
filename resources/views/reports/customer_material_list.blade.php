<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Material List</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            text-align: center; /* Center the entire body content */
            
        }
        .company-details {
            display: table; /* Use table display for alignment */
            margin: 0 auto; /* Center the table */
            margin-bottom: 20px; /* Space below the company details */
        }
        .company-logo {
            width: 100px; /* Adjust size as needed */
            height: auto;
        }
        .company-info {
            display: table-cell; /* Allow the info to behave like a table cell */
            vertical-align: middle; /* Center the content vertically */
            padding-left: 10px; /* Space between image and text */
            text-align: left; /* Align text to the left */
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
        {{ $company['phone'] }}
        <br>{{ $company['email'] }}</p>
    </div>
</div>

<div class="sales-report-title">
    <h3>Customer Material List</h3>
</div>

@php
    // Helper function to determine if a color is dark or light
    function isDarkColor($color) {
        $color = str_replace('#', '', $color);
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        return $brightness < 128; // True if dark, false if light
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
            <th style="width: 50%; text-align:center">Description</th>
            <th>Cost</th>
            <th style="width:15%">Total Cost</th>
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
                     {{-- ✅ Use accessor from model --}}
            <td style="width:10%; text-align:right">
                ${{ number_format($item->total_material_cost, 2) }}
            </td>

            {{-- ✅ Use actual material cost accessor --}}
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
