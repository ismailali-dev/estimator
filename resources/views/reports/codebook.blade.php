<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sales Report</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            text-align: center;
            font-size:13px;
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
            table-layout: fixed; /* Ensures equal width columns */
        }
        th, td {
            padding: 8px;
            text-align: left;
           
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
        }
        .category-heading {
            font-weight: bold;
            text-align: left;
            background-color: #f0f0f0;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="company-details">
        <div class="company-logo">
            <img src="{{ $logoPath }}" alt="Company Logo" style="width:100%">
        </div>
        <div class="company-info">
            <h2 style="margin:0px">{{ $company->name }}</h2>
            <p style="margin:0px">{{ $company->address }}<br>
             {{ $user->phone }}<br>
             {{ $user->email }}</p>
        </div>
        
      
    </div>

      <div class="sales-report-title">
        <h2> Code Book</h2>
        <br>
        
    </div>
    
    
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
            
            
    @php
        $uniqueCategories = $codes->pluck('product_group_id')->unique();
    @endphp

    @foreach ($uniqueCategories as $categoryId)
        @php
            $filteredCodes = $codes->where('product_group_id', $categoryId);
            $categoryName = $filteredCodes->first()->productGroup->group_name ?? 'No Category';
        @endphp

        @if ($filteredCodes->isNotEmpty())
            <table>
                <thead>
                    
                    <tr @if($user->hasSubscription('invoice')) style="background-color: {{ $backgroundColor }}; color: {{ $textColor }};" @endif>
                        <th style="width:9.5%;width: fit-content">Key Code</th> <!-- Equal width columns -->
                        <th style="width: 12%;text-align:right">Cost</th>
                        <th style="width: 5%;">Unit</th>
                        <th style="width: 60%;text-align:center">{{$categoryName}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filteredCodes as $code)
                        <tr>
                            <td style="color:#3481b9; text-decoration:underline;">{{ $code->code_name }}</td>
                            <td style="text-align:right">${{ number_format($code->labor_cost + $code->material_cost + $code->misc_cost, 2) }}</td>
                            <td>{{ $code->unit_of_measure }}</td>
                            <td>{{ $code->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No data available for this category.</p>
        @endif
    @endforeach

    
</body>
</html>


