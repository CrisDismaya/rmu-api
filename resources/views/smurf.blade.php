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
			font-family: sans-serif;
		}

        table {
            border-collapse: collapse;
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

		.pattern-width {
			width: 2.5%;
		}

		table {
			width: 100%;
		}

		.table-bordered {
			border-collapse: collapse;
		}

		.table-bordered,
		.table-bordered th,
		.table-bordered td {
			border: 1px solid black;
		}

        .border-col {
			border-top: 1px solid black;
			border-bottom: 1px solid black;
			border-left: 1px solid black;
			border-right: 1px solid black;
        }
		.border-top {
			border-top: 1px solid black;
		}
        .border-bottom {
			border-bottom: 1px solid black;
		}
        .border-left {
			border-left: 1px solid black;
		}
        .border-right {
			border-right: 1px solid black;
		}

		.text-center { text-align: center; }
		.text-right { text-align: right; }
		.text-danger { color: red; }

		.text-amount { padding-right: 15px; }

		.tr-space-1 { padding: 1px; }
		.tr-space-2 { padding: 2px; }
		.tr-space-3 { padding: 3px; }

		.box {
			margin: 0 auto;
			border: 1px solid black;
			background-color: transparent;
			width: 6px;
			height: 6px;
		}

		.box-filled {
			margin: 0 auto;
			border: 1px solid black;
			background-color: #000000;
			width: 6px;
			height: 6px;
		}

        .check {
            width: 15px;
            height: 15px;
            position: relative;
        }

        .check::after {
            content: '';
            position: absolute;
            top: 25%;
            left: 100%;
            transform: translate(-50%, -50%) rotate(55deg);
            width: 4px;
            height: 12px;
            border-bottom: 3px solid #000;
            border-right: 3px solid #000;
        }
	</style>

</head>

<body>
    <?php
        $dataExtract = $data['datas'];
        $info = json_decode($dataExtract);

        $partsExtract = $data['refurbish'];
		$parts = json_decode($partsExtract);

        $dataHistory = $data['smurf_approver'];
        $approver = json_decode($dataHistory);

        function formatToMoney($number) {
            return number_format($number, 2, '.', ',');
        }
    ?>


    <div style="position: relative; margin: 0 auto; width: 100%; font-family: sans-serif;">

		<table class="">
		{{-- <table class="table-bordered"> --}}
			<tr>
				<td class="pattern-width"></td> <!-- 1 -->
				<td class="pattern-width"></td> <!-- 2 -->
				<td class="pattern-width"></td> <!-- 3 -->
				<td class="pattern-width"></td> <!-- 4 -->
				<td class="pattern-width"></td> <!-- 5 -->
				<td class="pattern-width"></td> <!-- 6 -->
				<td class="pattern-width"></td> <!-- 7 -->
				<td class="pattern-width"></td> <!-- 8 -->
				<td class="pattern-width"></td> <!-- 9 -->
				<td class="pattern-width"></td> <!-- 10 -->
				<td class="pattern-width"></td> <!-- 11 -->
				<td class="pattern-width"></td> <!-- 12 -->
				<td class="pattern-width"></td> <!-- 13 -->
				<td class="pattern-width"></td> <!-- 14 -->
				<td class="pattern-width"></td> <!-- 15 -->
				<td class="pattern-width"></td> <!-- 16 -->
				<td class="pattern-width"></td> <!-- 17 -->
				<td class="pattern-width"></td> <!-- 18 -->
				<td class="pattern-width"></td> <!-- 19 -->
				<td class="pattern-width"></td> <!-- 20 -->
				<td class="pattern-width"></td> <!-- 21 -->
				<td class="pattern-width"></td> <!-- 22 -->
				<td class="pattern-width"></td> <!-- 23 -->
				<td class="pattern-width"></td> <!-- 24 -->
				<td class="pattern-width"></td> <!-- 25 -->
				<td class="pattern-width"></td> <!-- 26 -->
				<td class="pattern-width"></td> <!-- 27 -->
				<td class="pattern-width"></td> <!-- 28 -->
				<td class="pattern-width"></td> <!-- 29 -->
				<td class="pattern-width"></td> <!-- 30 -->
				<td class="pattern-width"></td> <!-- 31 -->
				<td class="pattern-width"></td> <!-- 32 -->
				<td class="pattern-width"></td> <!-- 33 -->
				<td class="pattern-width"></td> <!-- 34 -->
				<td class="pattern-width"></td> <!-- 35 -->
				<td class="pattern-width"></td> <!-- 36 -->
				<td class="pattern-width"></td> <!-- 37 -->
				<td class="pattern-width"></td> <!-- 38 -->
				<td class="pattern-width"></td> <!-- 39 -->
				<td class="pattern-width"></td> <!-- 40 -->
			</tr>
            <tr valign="top">
                <td class="" colspan="2" rowspan="100"></td>
                <td class="text-center" colspan="10" rowspan="1">
                    <img src="{{ public_path('image/logo.jpg') }}" width="85%" height="8%"  alt="">
                </td>
                <td class="text-center" colspan="26">
                    <span class="text-bold" style="font-size: 19px"> Trans Asiatic Finance Incorporated </span><br>
                    <span style="font-size: 11px">
                        Unit 13, 2nd Floor Beacon Commercila Place, Apollo 3, Moonwalk Village, Las Piñas City <br>
                        Email and Contact Number: tafi@transasiaticfin.ph / 0919-074-3252
                    </span> <br><br>
                    <span class="text-bold" style="font-size: 19px"> SURRENDERED MOTORCYCLE UNIT REFURBISHMENT FORM (SMURF) </span>
                </td>
                <td class="" colspan="2" rowspan="100"></td>
            </tr>
            <tr><td style="padding: 10px;" colspan="36"></td></tr>
            <tr>
                <td class="text-bold border-col" style="padding: 10px 10px" colspan="12">
                    <span style="font-size: 12px;"> Branch </span>
                </td>
                <td class="border-col" colspan="24" style="padding: 10px 10px"> {{ $info[0]->branch }} </td>
            </tr>
            <tr>
                <td class="text-bold border-col" style="padding: 10px 10px" colspan="12">
                    <span style="font-size: 12px;"> Date </span>
                </td>
                <td class="border-col" colspan="24" style="padding: 10px 10px"> {{ $info[0]->today }} </td>
            </tr>
            <tr><td style="padding: 20px;" colspan="36"></td></tr>
            <tr>
                <td colspan="36"><span class="text-bold"> MOTORCYCLE DETAILS: </span></td>
            </tr>
            <tr>
                <td class="text-bold border-col" style="padding: 10px 10px" colspan="12">
                    <span style="font-size: 12px;"> Ex-Owner </span>
                </td>
                <td class="border-col" colspan="24" style="padding: 10px 10px"> {{ $info[0]->exOwner_Borrower }} </td>
            </tr>
            <tr><td style="padding: 20px;" colspan="36"></td></tr>
            <tr>
                <td class="text-left" colspan="16">
                    <span class="text-bold"> PARTS </span>
                </td>
                <td colspan="2"></td>
                <td class="text-center" colspan="8">
                    <span class="text-bold"> STATUS </span>
                </td>
                <td colspan="2"></td>
                <td class="text-center" colspan="8">
                    <span class="text-bold"> COST </span>
                </td>
            </tr>

            <?php $total_missing_and_damaged_parts = 0; ?>
			@if (count($parts) > 0)
				@for ($i = 0; $i < count($parts); $i++)
                    <tr>
                        <td class="border-bottom text-left" colspan="16" style="padding: 10px 10px">
                            <span class="text-bold"> {{ $parts[$i]->parts_name }} </span>
                        </td>
                        <td colspan="2"></td>
                        <td class="border-bottom text-center" colspan="8" style="padding: 10px 10px">
                            <span class="text-bold"> {{ $parts[$i]->parts_status }} </span>
                        </td>
                        <td colspan="2"></td>
                        <td class="border-bottom text-right " colspan="8" style="padding: 10px 10px">
                            <span class="text-bold"> {{ formatToMoney($parts[$i]->parts_price) }} </span>
                        </td>
                    </tr>
                    <?php $total_missing_and_damaged_parts = $total_missing_and_damaged_parts + $parts[$i]->parts_price; ?>
				@endfor
			@else
                <tr>
                    <td class="border-bottom text-left" colspan="16" style="padding: 10px 10px">
                        <span class="text-bold"> &nbsp; </span>
                    </td>
                    <td colspan="2"></td>
                    <td class="border-bottom text-center" colspan="8" style="padding: 10px 10px">
                        <span class="text-bold"> &nbsp; </span>
                    </td>
                    <td colspan="2"></td>
                    <td class="border-bottom text-right " colspan="8" style="padding: 10px 10px">
                        <span class="text-bold"> &nbsp; </span>
                    </td>
                </tr>
                <tr>
                    <td class="border-bottom text-left" colspan="16" style="padding: 10px 10px">
                        <span class="text-bold"> &nbsp; </span>
                    </td>
                    <td colspan="2"></td>
                    <td class="border-bottom text-center" colspan="8" style="padding: 10px 10px">
                        <span class="text-bold"> &nbsp; </span>
                    </td>
                    <td colspan="2"></td>
                    <td class="border-bottom text-right " colspan="8" style="padding: 10px 10px">
                        <span class="text-bold"> &nbsp; </span>
                    </td>
                </tr>
                <tr>
                    <td class="border-bottom text-left" colspan="16" style="padding: 10px 10px">
                        <span class="text-bold"> &nbsp; </span>
                    </td>
                    <td colspan="2"></td>
                    <td class="border-bottom text-center" colspan="8" style="padding: 10px 10px">
                        <span class="text-bold"> &nbsp; </span>
                    </td>
                    <td colspan="2"></td>
                    <td class="border-bottom text-right " colspan="8" style="padding: 10px 10px">
                        <span class="text-bold"> &nbsp; </span>
                    </td>
                </tr>
            @endif
            <tr><td style="padding: 20px;" colspan="36"></td></tr>
            <tr>
                <td colspan="36"><span class="text-bold"> ADDED COST: </span></td>
            </tr>
            <tr>
                <td class="text-bold border-col" style="padding: 10px 10px" colspan="12">
                    <span style="font-size: 12px;"> Total </span>
                </td>
                <td class="text-right border-col" colspan="24" style="padding: 10px 20px"> {{ formatToMoney($total_missing_and_damaged_parts) }} </td>
            </tr>
            <tr><td style="padding: 20px;" colspan="36"></td></tr>
            <tr>
                <td class="text-center" colspan="12">
                    <span class="text-bold"> DECISION </span>
                </td>
                <td class="text-center" colspan="12">
                    <span class="text-bold"> REMARKS </span>
                </td>
                <td class="text-center" colspan="12">
                    <span class="text-bold"> DATE APPROVED </span>
                </td>
            </tr>
            @if (count($approver) > 0)
				@for ($i = 0; $i < count($approver); $i++)
                    <tr>
                        <td class=" text-bold border-col" style="padding: 10px 10px" colspan="12">
                            <span style="font-size: 12px;"> {{ $approver[$i]->fullname }} </span>
                        </td>
                        <td class="text-center text-bold border-col" style="padding: 10px 10px" colspan="12">
                            <span style="font-size: 12px;"> {{ $approver[$i]->remarks }} </span>
                        </td>
                        <td class="text-center text-bold border-col" style="padding: 10px 10px" colspan="12">
                            <span style="font-size: 12px;"> {{ $approver[$i]->date_approved }} </span>
                        </td>
                    </tr>
                @endfor
			@else
                <tr>
                    <td class=" text-bold border-col" style="padding: 10px 10px" colspan="12">
                        <span style="font-size: 12px;"> &nbsp; </span>
                    </td>
                    <td class="text-center text-bold border-col" style="padding: 10px 10px" colspan="12">
                        <span style="font-size: 12px;"> &nbsp; </span>
                    </td>
                    <td class="text-center text-bold border-col" style="padding: 10px 10px" colspan="12">
                        <span style="font-size: 12px;"> &nbsp; </span>
                    </td>
                </tr>
            @endif
        </table>
    </div>
</body>

</html>
