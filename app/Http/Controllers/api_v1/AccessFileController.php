<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\access_file;


class AccessFileController extends BaseController
{
	//
	public function createFileUpload(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'filename' => 'required',
			'isRequired' => 'required',
		]);

		if ($validator->fails()) {
			return $this->sendError('Validation Error.', $validator->errors());
		}

		$format = [
			'filename' => $request->filename,
			'isRequired' => $request->isRequired
		];

		access_file::create($format);

		return $this->sendResponse([], 'Filename added successfully.');
	}

	public function files()
	{
		$files = DB::table('files')->get();

		return $files;
	}

	public function updateFileUpload(Request $request, $id)
	{
		$validator = Validator::make($request->all(), [
			'filename' => 'required',
			'isRequired' => 'required',
			'status' => 'required',
		]);

		if ($validator->fails()) {
			return $this->sendError('Validation Error.', $validator->errors());
		}

		access_file::where('id', $id)->update($request->all());
		return $this->sendResponse([], 'Location updated successfully.');

		return $this->sendResponse([], 'Filename added successfully.');
	}
}
