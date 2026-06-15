<table align="center" style="width: 50%; border: 6px #0000FF double;">
    <thead>
    <tr style="background-color: #FF0000; text-align: center; color: #FFFFFF; font-weight: bold; ">
        <th style="width: 50pt">header a</th>
        <th style="width: 50">header          b</th>
        <th style="background-color: #FFFF00; border-width: 12px"><span style="background-color: #00FF00;">header c</span></th>
    </tr>
    </thead>
    <tbody>
    <tr><td style="border-style: dotted; border-color: #FF0000">1</td><td colspan="2">2</td></tr>
    <tr><td>This is <b>bold</b> text</td><td></td><td>6</td></tr>
    </tbody>
</table>



@php($isShow = false)
@if($isShow)
    <header class="header">
        <div style="height: 10px; width: 100%"></div>
        <table class="table">
            <tbody>
            <tr>
                <td style="width: 200px; height: 100px;" class="align-middle text-center">
                    @if($logo != "")
                        <img src="{{$logo}}" style="max-height: 100px; display: block; margin:0 auto;" class="img-fluid">
                    @else
                        &nbsp;
                    @endif
                </td>
                <td>
                    <table class="table">
                        <tbody>
                        <tr>
                            <td class="align-top">
                                <table class="table companyInfo">
                                    <tbody>
                                    <tr>
                                        <td class="text-left"><h3>{{$report["company_name"]}}</h3></td>
                                    </tr>
                                    <tr>
                                        <td class="text-left"><h5>{{$report["company_address"]}}</h5></td>
                                    </tr>
                                    <tr>
                                        <td class="text-left"><h5>{{$report["phone"]}}</h5></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td class="align-middle" style="width: 150px;">
                                <table>
                                    <tbody>
                                    <tr>
                                        <td style="width:1px; white-space: nowrap">Date:</td>
                                        <td class="text-right" style="white-space: nowrap">{{$report["date"]}}</td>
                                    </tr>
                                    <tr>
                                        <td style="width:1px; white-space: nowrap">Page:</td>
                                        <td class="text-right" style="white-space: nowrap">&nbsp; </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            </tbody>
        </table>
    </header>
    <footer class="footer">
        <table class="table">
            <tbody>
            <tr>
                <td class="text-center" style="width:30%">
                    <div class="footer-box">Customers Initials</div>
                </td>
                <td>&nbsp;</td>
                <td class="text-center" style="width:20%">
                    <div class="footer-box">Date</div>
                </td>
            </tr>
            </tbody>
        </table>
    </footer>
    <main>
        <div class="container">
            <div class="customerInfo">
                <table class="table">
                    <tbody>
                    <tr>
                        <td style="width:50%;">
                            <table class="table customerInfo">
                                <tbody>
                                <tr>
                                    <td class="text-left align-top label">Customer:</td>
                                    <td class="text-left info">
                                        {{$report["customer"]["full_name"]}}<br/>
                                        {{$report["customer"]["address"]}}<br/>
                                        {{$report["customer"]["city_name"]}}, {{$report["customer"]["state_name"]}}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                        <td style="width:50%">
                            <table class="table quoteInfo">
                                <tbody>
                                <tr>
                                    <td class="text-left label">Quote:</td>
                                    <td class="text-left info"></td>
                                </tr>
                                <tr>
                                    <td class="text-left label">Date:</td>
                                    <td class="text-left info">{{$report["invoice"]["estimate_date"]}}</td>
                                </tr>
                                <tr>
                                    <td class="text-left label">By:</td>
                                    <td class="text-left info">{{$report["report_by"]}}</td>
                                </tr>
                                <tr>
                                    <td class="text-left label">Desc:</td>
                                    <td class="text-left info"></td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="scope">
                <h5 class="bg-color heading" style="background: #ccc; padding: 0; margin: 0;">Scope of Work</h5>
                <div class="description">
                    <p>{{$report["invoice"]["estimate_scope"]}}</p>
                </div>
            </div>
            <div class="specifications">
                <table class="table bg-color" style="background: #ccc; width: 100%">
                    <tbody>
                    <tr>
                        <td style="width:1px" style="white-space: nowrap; padding:5px;"><h5 style="margin: 0; padding: 0;">#</h5></td>
                        <td style="text-align: center; padding: 5px;"><h5 style="margin:0; padding: 0; text-transform: uppercase">Specifications</h5></td>
                    </tr>
                    </tbody>
                </table>

            </div>
            <div class="invoiceDetail">
                @php($sno = 1)
                @php($grandTotal = 0)
                @foreach($report["invoice_detail"] as $item)
                    <div class="productGroup">
                        <h3 style="text-align: center; margin:15px 0 30px; padding:0;">{{$item["group_name"]}}</h3>
                        @foreach($item["group_items"] as $sheet)
                            <table style="width: 100%">
                                <tbody>
                                <tr>
                                    <td style="text-align: left; vertical-align: top; width: 1px; white-space: nowrap; padding: 2px 5px; border-bottom: 1px solid #000;">{{$sno}}</td>
                                    <td style="text-align: left; vertical-align: top; border-bottom: 1px solid #000;">{{$sheet->Code->description}} {{$sheet->quantity}} {{$sheet->unit}} ({{$sheet->Code->code_name}})</td>
                                </tr>
                                </tbody>
                            </table>
                            @php($grandTotal += ($sheet->quantity * $sheet->labor_cost) + ($sheet->quantity * $sheet->material_cost) )
                            @php($sno++)
                        @endforeach
                    </div>
                @endforeach
            </div>
            <!--    <div class="reportFooter">
                    <h1>Footer</h1>
                </div>-->
            <div class="invoiceFooter">
                <table class="table">
                    <tbody>
                    <tr>
                        <td><p class="instructions">This is a quotation based on Customer Specifications and by itself is not a contract to do Work. ProBuilders Express cannot be responsible for changes in Work or Price due to County, State or Federal
                                Building Requirements that were not Specified nor Based on Approved Plans. Items noted as
                                standard are subject to additional charge if changed to a custom installation by owner. </p></td>
                    </tr>
                    <tr>
                        <td class="grandTotal">Grand Total w/tax: $ {{number_format($grandTotal, 0)}}</td>
                    </tr>
                    <tr>
                        <td>
                            <table class="table">
                                <tbody>
                                <tr>
                                    <td class="text-center" style="width:25%"><div class="top-border-box">Customer Signature</div></td>
                                    <td class="text-center" style="width:25%"><div class="top-border-box">Date</div></td>
                                    <td class="text-center" style="width:25%"><div class="top-border-box">Pro-Builders Express</div></td>
                                    <td class="text-center" style="width:25%"><div class="top-border-box">Date</div></td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="lastLine">MUST BE SIGNED AND DATED ALONG WITH CONTRACT TO BECOME VALID.</td>
                    </tr>
                    </tbody>
                </table>

            </div>
        </div>
    </main>
@endif
