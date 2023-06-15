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
    public function createFileUpload(Request $request){
		$validator = Validator::make($request->all(), [
			'filename' => 'required',
		]);
	
		if ($validator->fails()) {
			return $this->sendError('Validation Error.', $validator->errors()); 
		}

		$format = [
			'filename' => $request->filename,
		];

		access_file::create($format);
    	
		return $this->sendResponse([], 'Filename added successfully.');
	}

    public function files(){
		$files = DB::table('files')
			->where('status', '=', '1')
			->get();

		return $files;
	}
}
