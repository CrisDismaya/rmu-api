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
            border-collapse: collapse;
		}

		.table-bordered {
			border-collapse: collapse;
		}

		.table-bordered,
		.table-bordered th,
		.table-bordered td {
			border: 1px solid black;
		}

		.border-bottom {
			border-bottom: 1px solid black;
		}

		.text-center { text-align: center; }
		.text-right { text-align: right; }
		.text-danger { color: red; }

		.text-amount { padding-right: 15px; }

		.tr-space-1 { padding: 5px; }
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
		$owners = ($info[0]->owners != '[]' ? json_decode($info[0]->owners, true) : '');

		$partsExtract = $data['parts'];
		$decodedParts = json_decode($partsExtract);

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
			<tr>
				<td class="text-bold" colspan="6"> Name of Company </td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="19">{{ $info[0]->company }}</td>
				<td class="" colspan="3"></td>
				<td class="text-bold" colspan="4"> MUISVA No. </td>
				<td class="text-center text-bold"> : </td>
				<td class="border-bottom text-center text-danger" colspan="6">{{ $info[0]->muisva_number }}</td>
			</tr>
			<tr><td class="tr-space-1" colspan="40"></td></tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="text-center text-bold" colspan="36">
					<span style="font-size: 16px; font-style: italic;"> Motorcycle Unit Inspection and Immediate Sales Value Approval Form (MUISVA) </span>
				</td>
				<td class="" colspan="2"></td>
			</tr>
			<tr><td class="tr-space-1" colspan="40"></td></tr>
			<tr>
				<td></td>
				<td class="text-center text-bold" colspan="38">
					<span style="font-size: 11px;"> Significance: This from shall be used by the valuation committee to determine the immediate sales value of the surrendered / repossessed motorcycle. </span>
				</td>
				<td></td>
			</tr>
			<tr><td class="tr-space-1" colspan="40"></td></tr>
			<tr>
				<td class="" colspan="12">Dealer's Store</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="27">{{ $info[0]->branch }}</td>
			</tr>
			<tr>
				<td class="" colspan="12">Originating Financing Store</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="27">{{ $info[0]->originating_financing_store }}</td>
			</tr>
			<tr>
				<td class=""> I </td>
				<td class="" class="">  </td>
				<td class="" colspan="12"> BACKGROUND OF THE ACCOUNT </td>
				<td class="" colspan="26">
					{{-- <span style="font-size: 11px; font-style: italic;"> To be fillled-up by FCA/SSA </span> --}}
				</td>
			</tr>
			<tr>
				<td class="" colspan=""></td>
				<td class="text-center" colspan="">1</td>
				<td class="" colspan="10">Latest Borrower's Name</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="10">{{ $info[0]->exOwner_Borrower }}</td>
				<td class="" colspan="1"></td>
				<td class="" colspan="5">Original Owner</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="10">{{ $info[0]->original_owner }}</td>
			</tr>
			<tr>
				<td class="" colspan="" rowspan="<?php echo $owners == '' ? '' : count($owners) ?>" valign="top"></td>
				<td class="text-center" colspan="" rowspan="<?php echo $owners == '' ? '' : count($owners) ?>" valign="top"></td>
				<td class="" colspan="10" rowspan="<?php echo $owners == '' ? '' : count($owners) ?>" valign="top">Times Repossessed </td>
				<td class="text-bold text-center" rowspan="<?php echo $owners == '' ? '' : count($owners) ?>" valign="top"> : </td>
				<td class="" colspan="10" rowspan="<?php echo $owners == '' ? '' : count($owners) ?>" valign="top">{{ $info[0]->times_repossessed }}</td>
				<td class="" colspan="1" rowspan="<?php echo $owners == '' ? '' : count($owners) ?>" valign="top"></td>
                @if ($owners != '')
                    <td class="" colspan="5">Prev. Owner 1</td>
                    <td class="text-bold text-center"> : </td>
                    <td class="border-bottom" colspan="10">{{ $owners[0]['exOwner'] }}</td>
                @else
                    <td class="" colspan="16"></td>
                @endif
			</tr>
			@if ($owners != '')
				@for ($i = 1; $i < count($owners); $i++)
					<tr>
						<td class="" colspan="5">Prev. Owner {{ $i + 1 }}</td>
						<td class="text-bold text-center"> : </td>
						<td class="border-bottom" colspan="10">{{ $owners[$i]['exOwner'] }}</td>
					</tr>
				@endfor
			@endif
			<tr>
				<td class="" colspan=""></td>
				<td class="text-center" colspan="">2</td>
				<td class="" colspan="10">Address</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="27">{{ $info[0]->address }}</td>
			</tr>
			<tr>
				<td class="" colspan=""></td>
				<td class="text-center" colspan="">3</td>
				<td class="" colspan="10">Loan Details</td>
				<td class="" colspan="28"></td>
			</tr>
			<tr>
				<td class="" colspan="3"></td>
				<td class="" colspan="9">Folder No.</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="10">{{ $info[0]->loan_number }}</td>
				<td class="" colspan="1"></td>
				<td class="" colspan="5">Loan Amount</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom text-right text-amount" colspan="10">{{ formatToMoney($info[0]->original_loan_amount) }}</td>
			</tr>
			<tr>
				<td class="" colspan="3"></td>
				<td class="" colspan="9">Date Granted</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="10">{{ $info[0]->date_released }}</td>
				<td class="" colspan="1"></td>
				<td class="" colspan="5">Total Payment</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom text-right text-amount" colspan="10">{{ formatToMoney($info[0]->total_payments) }}</td>
			</tr>
			<tr>
				<td class="" colspan="3"></td>
				<td class="" colspan="9">Last date of payment</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="10">{{ $info[0]->last_date_payments }}</td>
				<td class="" colspan="1"></td>
				<td class="" colspan="5">Principal Balance</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom text-right text-amount" colspan="10">{{ formatToMoney($info[0]->outstanding_loan_balance) }}</td>
			</tr>
			<tr>
				<td class="" colspan="23"></td>
				{{-- <td class="" colspan="9">Date Due</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="10">{{ $info[0]->date_due }}</td> --}}
				<td class="" colspan="1"></td>
				<td class="" colspan="5">Repo Date</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="10">{{ $info[0]->date_repossessed }}</td>
			</tr>
			<tr><td class="tr-space-1" colspan="40"></td></tr>
			<tr>
				<td class=""> II </td>
				<td class="" class="">  </td>
				<td class="" colspan="12"> DETAILS OF THE UNIT </td>
				<td class="" colspan="26">
					{{-- <span style="font-size: 11px; font-style: italic;"> To be fillled-up by FCA/SSA </span> --}}
				</td>
			</tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="10" valign="top">Brand</td>
				<td class="text-bold text-center" valign="top"> : </td>
				<td class="border-bottom" colspan="10" valign="top">{{ $info[0]->brand }}</td>
				<td class="" colspan="1"></td>
				<td class="" colspan="5" valign="top">Model</td>
				<td class="text-bold text-center" valign="top"> : </td>
				<td class="border-bottom" colspan="10" valign="top">{{ $info[0]->model }}</td>
			</tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="10">Engine No.</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="10">{{ $info[0]->engine_number }}</td>
				<td class="" colspan="1"></td>
				<td class="" colspan="5">Chassis No.</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="10">{{ $info[0]->chassis_number }}</td>
			</tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="10">MV File Number</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="10">{{ $info[0]->mv_file_number }}</td>
				<td class="" colspan="1"></td>
				<td class="" colspan="5">Year Model</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="10">{{ $info[0]->year_model }}</td>
			</tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="10">Plate No.</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="10">{{ $info[0]->plate_number }}</td>
				<td class="" colspan="1"></td>
				<td class="" colspan="5">ORCR Staths</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="10">{{ $info[0]->orcr_status }}</td>
			</tr>
			<tr><td class="tr-space-1" colspan="40"></td></tr>
			<tr>
				<td class=""> III </td>
				<td class="" colspan="">  </td>
				<td class="" colspan="12"> CONDITION OF THE UNIT </td>
				<td class="" colspan="26">
					{{-- <span style="font-size: 11px; font-style: italic;"> To be fillled-up by Store Mechanic </span> --}}
				</td>
			</tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="10">Classification</td>
				<td class="" colspan="2"></td>
				<td class="text-center" colspan=""><div class="{{ $info[0]->classification == 'A' ? 'box-filled' : 'box' }}"></div></td>
				<td class="" colspan="3">Class A</td>
				<td class="" colspan=""></td>

				<td class="text-center" colspan=""><div class="{{ $info[0]->classification == 'B' ? 'box-filled' : 'box' }}"></div></td>
				<td class="" colspan="3">Class B</td>
				<td class="" colspan=""></td>

				<td class="text-center" colspan=""><div class="{{ $info[0]->classification == 'C' ? 'box-filled' : 'box' }}"></div></td>
				<td class="" colspan="3">Class C</td>
				<td class="" colspan=""></td>

				<td class="text-center" colspan=""><div class="{{ $info[0]->classification == 'D' ? 'box-filled' : 'box' }}"></div></td>
				<td class="" colspan="3">Class D</td>
				<td class="" colspan=""></td>

				<td class="text-center" colspan=""><div class="{{ $info[0]->classification == 'E' ? 'box-filled' : 'box' }}"></div></td>
				<td class="" colspan="3">Class E</td>
				<td class="" colspan="2"></td>
			</tr>
            <tr><td class="tr-space-2" colspan="40"></td></tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="10">Complete / Incomplete Documents</td>
				<td class="" colspan="2"></td>

				<td class="text-center" colspan=""><div class="{{ $info[0]->classification_document_tag == 'CD' ? 'box-filled' : 'box' }}"></div></td>
				<td class="" colspan="12">Complete Documents</td>
				<td class="text-center" colspan=""><div class="{{ $info[0]->classification_document_tag == 'ID' ? 'box-filled' : 'box' }}"></div></td>
				<td class="" colspan="10">Incomplete Documents</td>
			</tr>
            <tr><td class="tr-space-2" colspan="40"></td></tr>
			<tr>
				<td class="" colspan="2" rowspan="3"></td>
				<td class="" colspan="10" rowspan="3" valign="top">Description</td>
				<td class="" colspan="2" rowspan="3"></td>
				<td class="text-center" colspan="" valign="top"><div class="{{ $info[0]->classification == 'A' ? 'box-filled' : 'box' }}"></div></td>
				<td class="" colspan="10">Good as new repossessed unit</td>
				<td class="" colspan="2"></td>
				<td class="text-center" colspan="" valign="top"><div class="{{ $info[0]->classification == 'D' ? 'box-filled' : 'box' }}"></div></td>
				<td class="" colspan="10">Major Repair Needed</td>
				<td class="" colspan="2"></td>
			</tr>
			<tr>
				<td class="text-center" colspan="" valign="top"><div class="{{ $info[0]->classification == 'B' ? 'box-filled' : 'box' }}"></div></td>
				<td class="" colspan="10">Presentable, Minimal Conditioning Needed</td>
				<td class="" colspan="2"></td>
				<td class="text-center" colspan="" valign="top"><div class="{{ $info[0]->classification == 'E' ? 'box-filled' : 'box' }}"></div></td>
				<td class="" colspan="10">Cannibalized/Totally Wrecked/Involved in an accident</td>
				<td class="" colspan="2"></td>
			</tr>
			<tr>
				<td class="text-center" colspan="" valign="top"><div class="{{ $info[0]->classification == 'C' ? 'box-filled' : 'box' }}"></div></td>
				<td class="" colspan="10">Minimal Repair Needed</td>
				<td class="" colspan="2"></td>
			</tr>
			<tr><td class="tr-space-1" colspan="40"></td></tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="38">Missing/Damage Parts</td>
			</tr>
			<tr>
				<td class="" colspan="3"></td>
				<td class="" colspan=""></td>
				<td class="" colspan="17">Parts</td>
				<td class="" colspan=""></td>
				<td class="text-center" colspan="4">Missing</td>
				<td class="" colspan=""></td>
				<td class="text-center" colspan="4">Damaged</td>
				<td class="" colspan=""></td>
				<td class="" colspan="8">Cost</td>
			</tr>
			<?php $total_missing_and_damaged_parts = 0; ?>
			@if (count($decodedParts) > 0)
				@for ($i = 0; $i < count($decodedParts); $i++)
					<tr>
						<td class="" colspan="4"></td>
						<td class="border-bottom" colspan="17"> {{ $decodedParts[$i]->parts_name }} </td>
						<td class="" colspan=""></td>
						<td class="text-center" colspan="4"><div class="{{ $decodedParts[$i]->parts_status == 'Missing' ? 'box-filled' : 'box' }}"></div></td>
						<td class="" colspan=""></td>
						<td class="text-center" colspan="4"><div class="{{ $decodedParts[$i]->parts_status == 'Damaged' ? 'box-filled' : 'box' }}"></div></td>
						<td class="" colspan=""></td>
						<td class="border-bottom text-right text-amount" colspan="8">{{ formatToMoney($decodedParts[$i]->parts_price) }}</td>
					</tr>
					<tr><td class="tr-space-2" colspan="40"></td></tr>
					<?php $total_missing_and_damaged_parts = $total_missing_and_damaged_parts + $decodedParts[$i]->parts_price; ?>
				@endfor
			@else
				<tr>
					<td class="" colspan="4"></td>
					<td class="border-bottom" colspan="17"> </td>
					<td class="" colspan=""></td>
					<td class="text-center" colspan="4"><div class="box"></div></td>
					<td class="" colspan=""></td>
					<td class="text-center" colspan="4"><div class="box"></div></td>
					<td class="" colspan=""></td>
					<td class="border-bottom text-right text-amount" colspan="8"></td>
				</tr>
				<tr><td class="tr-space-1" colspan="40"></td></tr>
				<tr>
					<td class="" colspan="4"></td>
					<td class="border-bottom" colspan="17"> </td>
					<td class="" colspan=""></td>
					<td class="text-center" colspan="4"><div class="box"></div></td>
					<td class="" colspan=""></td>
					<td class="text-center" colspan="4"><div class="box"></div></td>
					<td class="" colspan=""></td>
					<td class="border-bottom text-right text-amount" colspan="8"></td>
				</tr>
				<tr><td class="tr-space-1" colspan="40"></td></tr>
				<tr>
					<td class="" colspan="4"></td>
					<td class="border-bottom" colspan="17"> </td>
					<td class="" colspan=""></td>
					<td class="text-center" colspan="4"><div class="box"></div></td>
					<td class="" colspan=""></td>
					<td class="text-center" colspan="4"><div class="box"></div></td>
					<td class="" colspan=""></td>
					<td class="border-bottom text-right text-amount" colspan="8"></td>
				</tr>
				<tr><td class="tr-space-1" colspan="40"></td></tr>
			@endif

			{{-- total --}}
			<tr>
				<td class="" colspan="3"></td>
				<td class="" colspan="29">Total Missing / Damaged Parts Cost</td>
				<td class="border-bottom text-right text-amount" colspan="8" style="border-bottom: 2px double black;">{{ formatToMoney($total_missing_and_damaged_parts) }}</td>
			</tr>
			<tr><td class="tr-space-1" colspan="40"></td></tr>
			<tr>
				<td class=""> IV </td>
				<td class="" colspan="">  </td>
				<td class="" colspan="12"> STANDARD MATRIX </td>
				<td class="" colspan="26">
					<span style="font-size: 11px; font-style: italic;"> To be fillled-up by Accounting Head </span>
				</td>
			</tr>
			{{-- <tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="12"> Original Selling Price </td>
				<td class="" colspan="18"></td>
				<td class="border-bottom text-right text-amount" colspan="8">{{ formatToMoney($info[0]->original_srp) }}</td>
			</tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="12"> Less : Missing and Damaged Parts </td>
				<td class="" colspan="18"></td>
				<td class="border-bottom text-right text-amount" colspan="8">{{ formatToMoney($info[0]->original_srp - $total_missing_and_damaged_parts) }}</td>
			</tr> --}}
			<tr>
				<td class="" colspan="4"></td>
				<td class="" colspan="36"> Depreciation (From 1st Delivery to Current Date) </td>
			</tr>
			<tr><td class="tr-space-2" colspan="40"></td></tr>
			<tr>
				<td class="" colspan="3"></td>
				<td class="" colspan="11"> 1st to 6th months </td>
				<td class="border-bottom" colspan="2"><div class="{{ ($info[0]->standard_matrix_month >= 1 && $info[0]->standard_matrix_month <= 6) ? 'check' : ''  }}"></div></td>
				<td class="" colspan="3"> Months </td>
				<td class="" colspan=""></td>
				{{-- <td class="border-bottom" colspan="3"></td>
				<td class="" colspan="17"> Depreciation Rate </td> --}}
			</tr>
			<tr>
				<td class="" colspan="3"></td>
				<td class="" colspan="11"> 7th to 12th months </td>
				<td class="border-bottom" colspan="2"><div class="{{ ($info[0]->standard_matrix_month >= 7 && $info[0]->standard_matrix_month <= 12) ? 'check' : ''  }}"></div></td>
				<td class="" colspan="3"> Months </td>
				<td class="" colspan=""></td>
				{{-- <td class="border-bottom" colspan="3"></td>
				<td class="" colspan="17"> Depreciation Rate </td> --}}
			</tr>
			<tr>
				<td class="" colspan="3"></td>
				<td class="" colspan="11"> 13th to 24th months </td>
				<td class="border-bottom" colspan="2"><div class="{{ ($info[0]->standard_matrix_month >= 13 && $info[0]->standard_matrix_month <= 24) ? 'check' : ''  }}"></div></td>
				<td class="" colspan="3"> Months </td>
				<td class="" colspan=""></td>
				{{-- <td class="border-bottom" colspan="3"></td>
				<td class="" colspan="17"> Depreciation Rate </td> --}}
			</tr>
			<tr>
				<td class="" colspan="3"></td>
				<td class="" colspan="11"> 25th to Current Date </td>
				<td class="border-bottom" colspan="2"><div class="{{ $info[0]->standard_matrix_month >= 25 ? 'check' : ''  }}"></div></td>
				<td class="" colspan="3"> Months </td>
				<td class="" colspan=""></td>
				{{-- <td class="border-bottom" colspan="3"></td>
				<td class="" colspan="9"> Depreciation Rate </td> --}}
			</tr>
			<tr><td class="tr-space-2" colspan="40"></td></tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="30"> Total Depreciation </td>
                <?php
                    $rate = 0;
                    if($info[0]->standard_matrix_month >= 1 && $info[0]->standard_matrix_month <= 6){
                        $rate = .05;
                    }
                    else if($info[0]->standard_matrix_month >= 7 && $info[0]->standard_matrix_month <= 12){
                        $rate = .10;
                    }
                    else if($info[0]->standard_matrix_month >= 13 && $info[0]->standard_matrix_month <= 24){
                        $rate = .15;
                    }
                    else{
                        $rate = .20;
                    }

                    $total_depreciation = $info[0]->original_selling_price * $rate;
                    $total_smv = $info[0]->original_selling_price - ($total_missing_and_damaged_parts + $total_depreciation)
                            + ($info[0]->has_appraised != 'true' ? $info[0]->settled_total_cost : 0)
                ?>
				<td class="border-bottom text-right text-amount" colspan="8">{{ formatToMoney($total_depreciation) }}</td>
			</tr>
			<tr><td class="tr-space-2" colspan="40"></td></tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="30"> Added Cost - Refubishment </td>
				<td class="border-bottom text-right text-amount" colspan="8" style="border-bottom: 2px double black;">
                    {{ formatToMoney($info[0]->settled_total_cost) }}
                </td>
			</tr>
			<tr><td class="tr-space-2" colspan="40"></td></tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="30"> Standard Matrix Value </td>
				<td class="border-bottom text-right text-amount" colspan="8" style="border-bottom: 2px double black;">
                    {{ formatToMoney($total_smv) }}
                </td>
			</tr>
			<tr><td class="tr-space-1" colspan="40"></td></tr>
			<tr>
				<td class=""> V </td>
				<td class="" colspan="">  </td>
				<td class="" colspan="12"> DECISION </td>
				<td class="" colspan="11">
					<span style="font-size: 11px; font-style: italic;"> </span>
				</td>
				<td class="" colspan="7"> Date Approved </td>
				<td class="" colspan=""></td>
				<td class="" colspan="7"> Cash/Installment Basis </td>
			</tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="">1</td>
				<td class="" colspan="22"> Sell the unit (as is) with the appraised value of- </td>
                <?php
                    $appraisal_value = $info[0]->has_appraised == 'true' ? formatToMoney($info[0]->approved_appraised_price + $info[0]->settled_total_cost) : '';
                ?>
				<td class="border-bottom" colspan="7">{{ $info[0]->appraise_date_approved }}</td>
				<td class="" colspan=""></td>
				<td class="border-bottom text-right text-amount" colspan="7">{{ $appraisal_value }}</td>
			</tr>
			<tr><td class="tr-space-2" colspan="40"></td></tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="">2</td>
				<td class="" colspan="22"> Sell the unit After Repair at </td>
				<td class="border-bottom" colspan="7"></td>
				<td class="" colspan=""></td>
				<td class="border-bottom" colspan="7"></td>
			</tr>
			<tr>
				<td class="" colspan="3"></td>
				<td class="" colspan="22"> Note: Repair the unit and spend amounting to </td>
				<td class="text-center" colspan="">P</td>
				<td class="border-bottom" colspan="7"></td>
				<td class="" colspan="7"></td>
			</tr>
			<tr><td class="tr-space-2" colspan="40"></td></tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="" colspan="">3</td>
				<td class="" colspan="37"> Approving Authority (Any Two of HOVC Member of MANCOM, FBC, GM, Audit, Account) </td>
			</tr>
			<tr>
				<td class="" colspan="4"></td>
				<td class="text-center" colspan="15">Name of Approving Personnel</td>
				<td class="" colspan=""></td>
				<td class="" colspan="7">Position</td>
				<td class="" colspan=""></td>
				<td class="" colspan="5">Signature</td>
				<td class="" colspan=""></td>
				<td class="" colspan="6">Date</td>
			</tr>
			<tr>
				<td class="" colspan="4"></td>
				<td class="border-bottom" colspan="15"> &nbsp; </td>
				<td class="" colspan=""></td>
				<td class="border-bottom" colspan="7"></td>
				<td class="" colspan=""></td>
				<td class="border-bottom" colspan="5"></td>
				<td class="" colspan=""></td>
				<td class="border-bottom text-center" colspan="6"></td>
			</tr>
			<tr>
				<td class="" colspan="4"></td>
				<td class="border-bottom" colspan="15"> &nbsp; </td>
				<td class="" colspan=""></td>
				<td class="border-bottom" colspan="7"></td>
				<td class="" colspan=""></td>
				<td class="border-bottom" colspan="5"></td>
				<td class="" colspan=""></td>
				<td class="border-bottom text-center" colspan="6"></td>
			</tr>
			<tr>
				<td class="" colspan="4"></td>
				<td class="border-bottom" colspan="15"> &nbsp; </td>
				<td class="" colspan=""></td>
				<td class="border-bottom" colspan="7"></td>
				<td class="" colspan=""></td>
				<td class="border-bottom" colspan="5"></td>
				<td class="" colspan=""></td>
				<td class="border-bottom text-center" colspan="6"></td>
			</tr>
			<tr><td class="tr-space-3" colspan="40"></td></tr>
			<tr>
				<td class="" colspan=""></td>
				<td class="" colspan="39">INSTRUCTION</td>
			</tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="text-center" colspan="">1</td>
				<td class="" colspan="37">Accomplish the form properly with integrity and independence</td>
			</tr>
			<tr>
				<td class="" colspan="2"></td>
				<td class="text-center" colspan="">2</td>
				<td class="" colspan="37">Each units shall accomplished MUISVA. Whenever there is re-evaluation, attached the previous MUISVA of the unit of the new one.</td>
			</tr>
			<tr><td class="tr-space-1" colspan="40"></td></tr>
		</table>
	</div>

    <div style="position: relative; margin: 0 auto; width: 100%; font-family: sans-serif; page-break-before: always;">
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
            <tr>
				<td class="" colspan="2"></td>
				<td class="text-center text-bold" colspan="36">
					<span style="font-size: 16px; font-style: italic;"> Apprehension, Alarm, and Voilation Status </span>
				</td>
				<td class="" colspan="2"></td>
			</tr>
            <tr>
				<td class="" colspan="12">With Apprehension</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="27">{{ $info[0]->apprehension }}</td>
			</tr>
			<tr><td class="tr-space-2" colspan="40"></td></tr>
			<tr>
				<td class="" colspan="12">Apprehension Description</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="27">{{ $info[0]->apprehension_description == "" ? "-" : $info[0]->apprehension_description }}</td>
			</tr>
			<tr><td class="tr-space-2" colspan="40"></td></tr>
			<tr>
				<td class="" colspan="12">Summary</td>
				<td class="text-bold text-center"> : </td>
				<td class="border-bottom" colspan="27">{{ $info[0]->apprehension_summary == "" ? "-" : $info[0]->apprehension_summary }}</td>
			</tr>
        </table>
    </div>
</body>
</html>
