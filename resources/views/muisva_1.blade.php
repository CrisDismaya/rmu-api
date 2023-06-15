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
    </style>

</head>

<body>



    <div style="position: relative; margin: 0 auto; width: 100%; font-family: sans-serif">
        <table>
            <tr>
                <td>Name of Company :</td>
                <td></td>
                <td colspan="2">MUISVA No. : <u><span style="color:red;">&nbsp;&nbsp;&nbsp;&nbsp;12345&nbsp;&nbsp;&nbsp;&nbsp;<span></u></td>
            </tr>
        </table>
        <center><h3>Motorcycle Unit Inspection and Immediate Sales Value Appraisal Form (MUISVA)</h3></center>
        <center><h6>Significance: This form shall be used by the valuation committee to determine the immediate sales value of the surrendered / repossessed motorcycle.</h6></center>
       
        <table>
            <tr>
                <td>Dealer's Store :</td>
                <td>________________________________________________</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Originating Financing Store :</td>
                <td>________________________________________________</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>I &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;BACKGROUND OF THE ACCOUNT</td>
                <td>To be filled-up by FCA/SSA</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;1. Latest Borrower's Name</td>
                <td>________________________________</td>
                <td>Original Owner</td>
                <td>________________________________</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2. Address</td>
                <td colspan="3">_______________________________________________________________________________________________</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3. Loan Details</td>
                <td colspan="3"></td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Folder No.</td>
                <td>________________________________</td>
                <td>Loan Amount :</td>
                <td>________________________________</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Date Granted</td>
                <td>________________________________</td>
                <td>Total Payments :</td>
                <td>________________________________</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Date Due</td>
                <td>________________________________</td>
                <td>Principal Balance :</td>
                <td>________________________________</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Last Date Of Payment</td>
                <td>________________________________</td>
                <td>Repo Date :</td>
                <td>________________________________</td>
            </tr>
            <tr>
                <td>II &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;DETAILS OF THE UNIT</td>
                <td>To be filled-up by FCA/SSA</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Brand</td>
                <td>________________________________</td>
                <td>Model</td>
                <td>________________________________</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Engine No.</td>
                <td>________________________________</td>
                <td>Chassis No.</td>
                <td>________________________________</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;OR No.</td>
                <td>________________________________</td>
                <td>CRE No.</td>
                <td>________________________________</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Plate No.</td>
                <td>________________________________</td>
                <td>BR / ARV No.</td>
                <td>________________________________</td>
            </tr>
            <tr>
                <td>III&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;CONDITION OF THE UNIT</td>
                <td>To be filled-up by Store Mechanic</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Classification</td>
                <td colspan="3"> ( )Class A &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ( )Class B &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ( )Class C
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ( )Class D &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ( )Class E
                </td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Complete / Incomplete Documents</td>
                <td colspan="3"> ( )CD &nbsp;&nbsp;( )ID&nbsp;&nbsp;&nbsp; ( )CD &nbsp;&nbsp;( )ID&nbsp;&nbsp;&nbsp; ( )CD
                &nbsp;&nbsp;( )ID&nbsp;&nbsp;&nbsp; ( )CD &nbsp;&nbsp;( )ID&nbsp;&nbsp;&nbsp; ( )CD &nbsp;&nbsp;( )ID
                </td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Description</td>
                <td colspan="3"> ( )Good as new repossessed unit &nbsp;&nbsp;( )Minimal Repair and Refurbishment &nbsp;&nbsp;
                    ( )Major Repair and Refurbishment &nbsp;&nbsp;( )Cannibalized&nbsp;&nbsp; ( )Meet an accident
                &nbsp;&nbsp;( )Totally wrecked
                </td>
            </tr>
            <tr>
                <td colspan="2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Missing / Damages Parts and Accessories</td>
                <td colspan="2"></td>
            </tr>
        </table>

    </div>


</body>

</html>