<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$Title}}</title>
    <link rel="stylesheet" href="{{ public_path().'/vendor/bootstrap/bootstrap.min.css' }}">
    <script src="{{ public_path().'/vendor/bootstrap/jquery-3.3.1.slim.min.js' }}"></script>
    <script src="{{ public_path().'/vendor/bootstrap/bootstrap.min.js' }}"></script>

    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
        }

        .tbl-contents {
            width: 100%;
            /* border-collapse: collapse; */
            border: 1px solid #000;
        }

        .tbl-contents td+td {
            border-left: 1px solid #000;
        }

        .tbl-contents td+td {

            /* height: 100px; */
        }

        th {
            border: 1px solid #000;
            font-size: 11px;
            padding: 5px;
            text-align: center;
            text-transform: uppercase;
        }

        td {
            font-size: 11px !important;
            padding: 10px;
            vertical-align: top;
        }

        .tbl-col-1 {
            width: 5%;
        }

        .tbl-col-2 {
            width: 10%;
        }

        .tbl-col-3 {
            width: 50%;
        }

        .tbl-col-4 {
            width: 8%;
        }

        .tbl-col-5 {
            width: 13%;
        }


        .signatures label {
            font-size: 13px;
        }

        .signatures .heading {
            margin-bottom: 30px;
            font-style: italic;
        }

        .text-bold {
            font-weight: bold;
        }

        .top-table label {
            font-size: 11px !important;
        }
        .td-class {
            border:solid 1px;
            margin: 0;
            /* border-right:solid 1px;
            border-bottom:solid 1px; */
        }
        table {
        width: 100%;
        }
     
        #tb2 {
            max-width: 750px;
        }
    </style>

</head>

<body>
    <?php
        $dataExtract = $data['datas'];
        $report_src = $data['report_src'];
        $decodedData = json_decode($dataExtract);
    ?>


    <div style="position: relative; margin: 0 auto; width: 100%; font-family: sans-serif">
        <table>
                <tr>
                    <td  style="width:15%">
                        <img style="display:inline-block; margin-left: 15px;" src="{{ public_path('image/rdaf-logo.jpeg') }}" width="110%"   alt="">
                    </td>
                    <td  style="width:85%; padding-top:30px;">
                    
                    <center>  <h3>Trans Asiatic Finance Incorporated</h3>
                    Unit 13, 2nd Floor Beacon Commercial Place, Apollo 3, Moonwalk Village, Las Piñas City
                            Email and Contact Number: tafi@transasiaticfin.ph / 0919-074-3252
                            <h3>ROPA DISPOSAL APPROVAL FORM (RDAF)</h3>
                    </center>
                    </td>
                </tr>
        </table>

        @if($report_src != "inventory")
        <table>
            <tr>
                <td class="td-class">
                    Branch
                </td>
                <td class="td-class">
                   {{ $decodedData[0]->branch}}
                </td>
                <td class="td-class">
                    Date
                </td>
                <td class="td-class">
                    {{ date('F j, Y') }}
                </td>
            </tr>
            <tr>
                <td class="td-class">
                    Name of Buyer
                </td>
                <td class="td-class">
                {{ $decodedData[0]->customer}}
                </td>
                <td class="td-class">
                    Primary ID
                </td>
                <td class="td-class">
                    {{ $decodedData[0]->primary_id }}
                </td>
            </tr>
            <tr>
                <td class="td-class">
                    Address
                </td>
                <td class="td-class">
                {{ $decodedData[0]->address}}
                </td>
                <td class="td-class">
                    Alternative ID
                </td>
                <td class="td-class">
                {{ $decodedData[0]->alternative_id}}
                </td>
            </tr>
            <tr>
                <td class="td-class">
                    Nationality
                </td>
                <td class="td-class">
                {{ $decodedData[0]->nationality}}
                </td>
                <td class="td-class">
                     ID No.
                </td>
                <td class="td-class">
                {{ $decodedData[0]->primary_id_no}}
                </td>
            </tr>
            <tr>
                <td class="td-class">
                    Source Of Income
                </td>
                <td class="td-class">
                {{ $decodedData[0]->source_of_income}}
                </td>
                <td class="td-class">
                     ID No.
                </td>
                <td class="td-class">
                {{ $decodedData[0]->alternative_id_no}}
                </td>
            </tr>

            <tr>
                <td class="td-class">
                    Marital Status
                </td>
                <td class="td-class">
                {{ $decodedData[0]->marital_status}}
                </td>
                <td class="td-class">
                    
                </td>
                <td class="td-class">
                  
                </td>
            </tr>
            <tr>
                <td class="td-class">
                    Date Of Birth
                </td>
                <td class="td-class">
                {{ $decodedData[0]->date_birth}}
                </td>
                <td class="td-class">
                    
                </td>
                <td class="td-class">
                    
                </td>
            </tr>
            <tr>
                <td class="td-class">
                    Place Of Birth
                </td>
                <td class="td-class">
                {{ $decodedData[0]->birth_place}}
                </td>
                <td class="td-class">
                    
                </td>
                <td class="td-class">
                    
                </td>
            </tr>
        </table>
        @endif
            <br />
            <span><small>Motorcycle Details</small></span>
            <table >
                <tr id="tb2">
                    <td class="td-class" style="width:8%">Loan No.</td>
                    <td class="td-class" style="width:8%">Originating FinancingStore</td>
                    <td class="td-class" style="width:8%">Date Released</td>
                    <td class="td-class" style="width:8%">Date Repossessed</td>
                    <td class="td-class" style="width:8%">Aging Days</td>
                    <td class="td-class" style="width:8%">Ex-Owner</td>
                    <td class="td-class" style="width:8%">Brand and Model</td>
                    <td class="td-class" style="width:8%">Original Loan Amount</td>
                    <td class="td-class" style="width:8%">Outstanding Loan Balance</td>
                    <td class="td-class" style="width:8%">Serial Number</td>
                    <td class="td-class" style="width:8%">Odometer</td>
                    <td class="td-class" style="width:8%">Total Payments</td>
                </tr>
                <tr id="tb2">
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->loan_number}}</td>
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->financing_store}}</td>
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->date_sold}}</td>date_sold
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->date_surrender}}</td>
                    <td class="td-class" style="width:8%">Aging Days</td>
                    <td class="td-class" style="width:8%"> {{ $decodedData[0]->customer}}</td>
                    <td class="td-class" style="width:8%"> {{ $decodedData[0]->brand}}  {{ $decodedData[0]->model}}</td>
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->srp}}</td>
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->principal_balance}}</td>
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->engine}}{{ $decodedData[0]->chassis}}</td>
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->odo_meter}}</td>
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->total_payment}}</td>
                </tr>
            </table>
            <br />
            <table >
                <tr>
                    <td class="td-class" style="width:20%">Current ROPA Price</td>
                    <td class="td-class" style="width:80%"><span style="color:red;">{{ $decodedData[0]->principal_balance}}</span></td>
                </tr>
            </table>
        
            <br />
            <span><small>Branch / Dealer Recommendation</small></span>
            <table border="1">
                <tr id="tb2">
                    <td class="td-class" style="width:8%">Requested Price</td>
                    <td class="td-class" style="width:8%">Rate</td>
                    <td class="td-class" style="width:8%">Rebate</td>
                    <td class="td-class" style="width:8%">Monthly</td>
                    <td class="td-class" style="width:8%">Net Amortization</td>
                    <td class="td-class" style="width:8%">Downpayment</td>
                    <td class="td-class" style="width:8%">Term (Month)</td>
                   
                </tr>
                <tr id="tb2">
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->approved_price}}</td>
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->rate}} %</td>
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->rebate}}</td>
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->monthly_amo}}</td>
                    <td class="td-class" style="width:8%">{{ ($decodedData[0]->monthly_amo - $decodedData[0]->rebate) }}</td>
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->dp}}</td>
                    <td class="td-class" style="width:8%">{{ $decodedData[0]->terms}}</td>
                   
                </tr>
            </table>
            <br />

            <span><small>Final Approval</small></span>
            <table border="1">
                <thead>
                    <tr>
                        <th>Approved By</th>
                        <th>Date</th>
                        <th>Approved Price</th>
                        <th>Remarks / Signature</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td >JOEMAR A. VALENCIA</td>
                        <td >{{ $decodedData[0]->date_approved}}</td>
                        <td >{{ $decodedData[0]->approved_price}}</td>
                        <td ></td>
                        
                    </tr>
                    <tr>
                        <td >RANDY C. TORRES</td>
                        <td >{{ $decodedData[0]->date_approved}}</td>
                        <td >{{ $decodedData[0]->approved_price}}</td>
                        <td ></td>
                      
                    </tr>
            </tbody>
            </table>

    </div>


</body>

</html>