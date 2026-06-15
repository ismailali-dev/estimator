<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
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
            padding-left: 20px; /* Space between image and text */
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
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        .sales-report-title {
            margin-bottom: 10px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="company-details">
        <div class="company-logo">
            <img src="{{ asset($company['logo']) }}" alt="Company Logo" style="width:100%">
        </div>
        <div class="company-info">
            <h2 style="margin:0px">{{ $company['name'] }}</h2>
            <p style="margin:0px">{{ $company['address'] }}<br>
           {{ $company['phone'] }}
            <br>{{ $company['email'] }}</p>
        </div>
    </div>

    <div class="sales-report-title">
        <h3>Sales Report</h3>
        
    </div>
    
   <table style="border: 0; border-collapse: collapse; width: 100%;">
    <tbody>
        <tr>
            <td style="border: 0; padding: 0; margin: 0;">
                <p style="margin: 0;"><span style="font-weight: bold;">Estimator:</span> {{ @$estimator }}</p>
            </td>
            <td style="border: 0; text-align: right; padding: 0; margin: 0;">
                <p style="margin: 0; font-size: 14px;">
    <span style="font-weight: bold;">From:</span> <span style="color: #555;">{{ $fromDate }}</span>
    <span style="font-weight: bold;">- To:</span> <span style="color: #555;">{{ $toDate }}</span>
</p>
            </td>
        </tr>
    </tbody>
</table>
     @php
    
                // Helper function to determine if a color is dark or light
                function isDarkColor($color) {
                    // Remove the # from the color if it's a hex value
                    $color = str_replace('#', '', $color);
            
                    // Convert hex color to RGB
                    $r = hexdec(substr($color, 0, 2));
                    $g = hexdec(substr($color, 2, 2));
                    $b = hexdec(substr($color, 4, 2));
            
                    // Calculate brightness using the YIQ formula
                    $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
            
                    return $brightness < 128; // True if dark, false if light
                }
            
               
                $backgroundColor = $user->hasSubscription('invoice') ? '#'.$color : '#ffffff';
                 
                $textColor = isDarkColor($backgroundColor) ? '#ffffff' : '#000000';
            
               
            @endphp
    
    <table>
        <thead>
            <tr @if($user->hasSubscription('invoice')) style="background-color: {{ $backgroundColor }}; color: {{ $textColor }};" @endif>
                <th>Date</th>
                <th>Quote #</th>
                <th>Customer</th>
                <th>Address</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sales as $sale)
                <tr>
                    <td>{{ $sale['date'] }}</td>
                    <td>{{ $sale['quote_key'] }}</td>
                    <td>{{ $sale['customer']['name'] }}</td>
                    <td>{{ $sale['customer']['address'] }}</td>
                    <td style="text-align:right">${{ number_format((float) preg_replace('/[^\d.]/', '', $sale['amount']), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

   
</body>
</html>
