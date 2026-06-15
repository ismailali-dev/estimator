<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Estimate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
   
    <script src="https://szimek.github.io/signature_pad/js/signature_pad.umd.min.js"></script> <!-- Include Signature Pad library -->
    <style>
        body {
            padding: 20px 30px;
            zoom: 60%;
        }
        .header-title {
            color: #0dcaf0;
            margin: 0px;
        }
        .bg-color {
            color: #000;
            font-weight: bold;
            padding: 10px;
            text-align: center;
        }
        .total {
            font-weight: bold;
            font-size: larger;
        }
        .spec-table .col-1, .spec-table .col-2, .spec-table .col-7 { }
        
        .signature-section {
            margin-top: 50px;
        }
        .signature-line {
            width: 90%;
            border-top: 1px solid #000;
            text-align: center;
            margin: 0 auto;
            cursor: pointer; /* Add cursor pointer to indicate clickable area */
        }
        p { margin: 0px; }
        .col-md-6 { width: 50%; }
        .col-1 { }
        .col-9 { flex: 0 0 auto; align-items: center; display: flex; }
        label.fw-bold { padding-right: 5px; }
        .col-1 { width: 5%; }
        .col-8 { width: 77%; }
        .col-1-5 { width: 9%; padding: 0px; }
        .row.spec-table { margin-bottom: 6px; }
        .main-heading { font-weight: bold; }
        .text-justify { text-align: justify; }
        .company-logo { width: 100px; /* Adjust size as needed */ height: auto; margin-right: 20px; }
        
        .signatureanddatecolumn{
            position:relative;
        }
       .signatureanddatecolumn .inner {
            height: 48px;
            padding-bottom: 10px;
            display: flex;
            align-items: end;
            justify-content: center;
        }
        .signatureanddatecolumn .inner img {
    max-width: unset;
    height: 100px;
}
        
    </style>
</head>
<body>
<div class="container my-4">
    <div class="row mb-4">
        <div class="col-md-12 text-center text-md-start d-flex justify-content-center">
            <div class="company-logo">
                <img src="{{ $logoPath }}" alt="Company Logo" style="width:100%">
            </div>
            <div style="text-align:left">
                <h1 class="header-title" style="color:#000">{{@$company->name}}</h1>
                <p>{{@$user->phone}}</p>
                <p>{{@$user->email}}</p>
                <p>{{@$company->address}}</p>
            </div>
        </div>
    </div>

    <!-- Estimate and Customer Details -->
    <div class="row mb-3">
        <div class="col-md-12 text-center">
            <h2 class="mb-3 main-heading"><strong>Estimate</strong></h2> 
        </div>
        <div class="col-md-6">
            <label class="fw-bold">Estimate No:</label>{{$estimate->key}}
        </div>
        <div class="col-md-6" style="text-align: end;">
            <label class="fw-bold">Estimate Date:</label> {{$estimate->created_at->format('M j, Y')}}
        </div>
    </div>

    <!-- Customer Details -->
    <div class="row">
        <div class="col-md-6">
            <h5 class="fw-bold">Customer Details: </h5>
            <p><label class="fw-bold">Name:</label>{{@$customer->name}}</p>
            <p><label class="fw-bold">Phone:</label>{{@$customer->phone}}</p>
            <p><label class="fw-bold">Address:</label>{{@$customer->address}}</p>
        </div>
        <div class="col-md-6" style="text-align: right;">
            <h5 class="fw-bold">Quote Details</h5>
            <p><label class="fw-bold">Estimate Type:</label>{{@$estimate->estimateType->name}}</p>
            <p><label class="fw-bold">Quoted By:</label>{{@$user->first_name.' '. @$user->last_name}}</p>
        </div>
    </div>

    <!-- Scope of Work -->
    <div class="row ">
        <div class="col-12">
            <h5 class="fw-bold">Scope of Work:</h5>
            <p>{{$estimate->estimate_scope}}</p>
        </div>
    </div>

    <!-- Specifications -->
    <div class="row">
        <div class="col-12">
            <h5 class="fw-bold">Specifications:</h5>
        </div>
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
        $currentProductGroup = null;
        $sno = 0; // Serial number starts at 0
    @endphp

    @foreach ($sheets as $sheet)
        @php
            $productGroup = optional($sheet->code->ProductGroup)->group_name;
        @endphp

        @if ($currentProductGroup !== $productGroup)
            @php
                $currentProductGroup = $productGroup;
                $sno = 0; // Reset serial number
            @endphp
            <div class="row spec-table">
                <div class="col-12 bg-color" @if($user->hasSubscription('invoice')) style="background-color: {{ $backgroundColor }}; color: {{ $textColor }};" @endif>
                    {{ $currentProductGroup }}
                </div>
            </div>
        @endif

        @php $sno++; @endphp

        <div class="row spec-table">
            <div class="col-1">{{ $sno }}.</div>
            <div class="col-8">{{ optional($sheet->code)->description }}</div>
            <div class="col-1-5 text-center" style="text-align:right">{{ optional($sheet)->quantity }}</div>
            <div class="col-1-5 text-center" style="text-align:right">{{ optional($sheet)->unit }}</div>
        </div>
    @endforeach

    <br>

    <div class="row ">
        <div class="col-md-12 text-end">
            <p class="total" >
                Grand Total w/tax: ${{$grand_total}}
            </p>
        </div>
    </div>

    <!-- Terms Section -->
    <!--<div class="row mt-4">-->
    <!--    <div class="col-12">-->
    <!--        <p class="text-justify">This is a quotation based on Customer Specifications and by itself is not a contract to do Work. Pro-Builders Express cannot be responsible for changes in Work or Price due to County, State or Federal Building Requirements that were not Specified nor Based on Approved Plans. Items noted as standard are subject to an additional charge if changed to a custom installation by the owner.:</p>-->
    <!--    </div>-->
    <!--</div>-->
    <br><br>

   <!-- Signature Section -->
    <!-- Signature Section -->
    <!-- Signature Section -->
<div class="row text-center signature-section">
    <div class="col-3 signatureanddatecolumn">
        <div class="inner">
            @if(isset($signatureData['customer_signature']) && !empty($signatureData['customer_signature']))
                <img src="{{ $signatureData['customer_signature'] }}" alt="Customer Signature" >
            @endif
        </div>
        <div class="signature-line" id="customer-signature">Customer Signature</div>
    </div>
    <div class="col-3 signatureanddatecolumn">
        <div class="inner">
            <span>{{ @$signatureData['customer_signed_at'] ? \Carbon\Carbon::parse($signatureData['customer_signed_at'])->format('m/d/Y') : '' }}</span>
        </div>
        <div class="signature-line">Date</div>
    </div>
    
   <div class="col-3 signatureanddatecolumn">
        <div class="inner">
            @if(isset($signatureData['estimator_signature']) && !empty($signatureData['estimator_signature']))
                <img src="{{ $signatureData['estimator_signature'] }}" alt="Estimator Signature" >
            @endif
        </div>
        <div class="signature-line">{{ @$company->name }}</div>
    </div>
   
    <div class="col-3 signatureanddatecolumn">
        <div class="inner">
            <span>{{ @$signatureData['estimator_signed_at'] ? \Carbon\Carbon::parse($signatureData['estimator_signed_at'])->format('m/d/Y') : '' }}</span>
        </div>
        <div class="signature-line">Date</div>
    </div>
</div>


</div>




</body>
</html>
