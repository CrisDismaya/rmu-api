<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class UserSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{

		//default branches

		DB::beginTransaction();
		DB::unprepared('SET IDENTITY_INSERT branches ON');
		
		DB::table('branches')->insert([
			'id'       => "1",
			'name' => 'Head Office',
			'status'       => "1",
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);
		DB::table('branches')->insert([
			'id'       => "2",
			'name' => 'Branch 2',
			'status'       => "1",
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);
		DB::table('branches')->insert([
			'id'       => "3",
			'name' => 'Branch 3',
			'status'       => "1",
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);
		DB::table('branches')->insert([
			'id'       => "4",
			'name' => 'Branch 4',
			'status'       => "1",
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);
		DB::table('branches')->insert([
			'id'       => "5",
			'name' => 'Branch 5',
			'status'       => "1",
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);

		DB::unprepared('SET IDENTITY_INSERT branches OFF');
		DB::unprepared('SET IDENTITY_INSERT brands ON');
		//brand
		DB::table('brands')->insert([ 'id' => "1", 'code' => 'BC-10001', 'brandname' => "YAMAHA", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('brands')->insert([ 'id' => "2", 'code' => 'BC-10002', 'brandname' => "SUZUKI", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('brands')->insert([ 'id' => "3", 'code' => 'BC-10003', 'brandname' => "HONDA", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('brands')->insert([ 'id' => "4", 'code' => 'BC-10004', 'brandname' => "KAWASAKI", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('brands')->insert([ 'id' => "5", 'code' => 'BC-10005', 'brandname' => "KYMCO", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);

		DB::unprepared('SET IDENTITY_INSERT brands OFF');
		DB::unprepared('SET IDENTITY_INSERT unit_colors ON');

		//default color
		DB::table('unit_colors')->insert([ 'id' => "1", 'name' => 'MAGENTA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_colors')->insert([ 'id' => "2", 'name' => 'MATTE GRAY', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_colors')->insert([ 'id' => "3", 'name' => 'MATTE BLACK', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_colors')->insert([ 'id' => "4", 'name' => 'MATTE BLACK', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_colors')->insert([ 'id' => "5", 'name' => 'MATTE ORANGE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_colors')->insert([ 'id' => "6", 'name' => 'MATTE GREEN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_colors')->insert([ 'id' => "7", 'name' => 'MATTE GRAY', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_colors')->insert([ 'id' => "8", 'name' => 'MATTE PURPLE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_colors')->insert([ 'id' => "9", 'name' => 'MATTE BLUE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);

		DB::unprepared('SET IDENTITY_INSERT unit_colors OFF');
		DB::unprepared('SET IDENTITY_INSERT customer_profile ON');

		//default customer profile
		DB::table('customer_profile')->insert([
			'id'       => "1",
			'acumatica_id' => '1',
			'firstname' => 'Customer 1',
			'middlename' => null,
			'lastname' => '',
			'contact' => '13113131313',
			'Address' => 'Address',
			'provinces' => 1,
			'cities' => 1,
			'barangays' => 1,
			'zip_code' => 1870,
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);
		DB::table('customer_profile')->insert([
			'id'       => "2",
			'acumatica_id' => '2',
			'firstname' => 'Customer 2',
			'middlename' => null,
			'lastname' => '',
			'contact' => '13113131313',
			'Address' => 'Address',
			'provinces' => 1,
			'cities' => 1,
			'barangays' => 1,
			'zip_code' => 1870,
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);
		DB::table('customer_profile')->insert([
			'id'       => "3",
			'acumatica_id' => '3',
			'firstname' => 'Customer 3',
			'middlename' => null,
			'lastname' => '',
			'contact' => '13113131313',
			'Address' => 'Address',
			'provinces' => 1,
			'cities' => 1,
			'barangays' => 1,
			'zip_code' => 1870,
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);

		DB::unprepared('SET IDENTITY_INSERT customer_profile OFF');
		DB::unprepared('SET IDENTITY_INSERT unit_models ON');
	
		DB::table('unit_models')->insert([ 'id' => "1", 'brand_id' => '1', 'inventory_code' => 'INV-10001', 'model_name' => 'Model 1', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_models')->insert([ 'id' => "2", 'brand_id' => '1', 'inventory_code' => 'INV-10002', 'model_name' => 'Model 2', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_models')->insert([ 'id' => "3", 'brand_id' => '2', 'inventory_code' => 'INV-10003', 'model_name' => 'Model 3', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_models')->insert([ 'id' => "4", 'brand_id' => '2', 'inventory_code' => 'INV-10004', 'model_name' => 'Model 4', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_models')->insert([ 'id' => "5", 'brand_id' => '3', 'inventory_code' => 'INV-10005', 'model_name' => 'Model 5', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_models')->insert([ 'id' => "6", 'brand_id' => '4', 'inventory_code' => 'INV-10006', 'model_name' => 'Model 6', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_models')->insert([ 'id' => "7", 'brand_id' => '4', 'inventory_code' => 'INV-10007', 'model_name' => 'Model 7', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('unit_models')->insert([ 'id' => "8", 'brand_id' => '5', 'inventory_code' => 'INV-10008', 'model_name' => 'Model 8', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);

		DB::unprepared('SET IDENTITY_INSERT unit_models OFF');
		DB::unprepared('SET IDENTITY_INSERT color_mappings ON');

		DB::table('color_mappings')->insert([ 'id' => "1", 'color_id' => '1', 'model_id' => '1', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('color_mappings')->insert([ 'id' => "2", 'color_id' => '2', 'model_id' => '1', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('color_mappings')->insert([ 'id' => "3", 'color_id' => '3', 'model_id' => '2', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('color_mappings')->insert([ 'id' => "4", 'color_id' => '4', 'model_id' => '2', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('color_mappings')->insert([ 'id' => "5", 'color_id' => '5', 'model_id' => '3', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('color_mappings')->insert([ 'id' => "6", 'color_id' => '6', 'model_id' => '4', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('color_mappings')->insert([ 'id' => "7", 'color_id' => '7', 'model_id' => '4', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		DB::table('color_mappings')->insert([ 'id' => "8", 'color_id' => '8', 'model_id' => '5', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), ]);
		
		DB::unprepared('SET IDENTITY_INSERT color_mappings OFF');
		DB::unprepared('SET IDENTITY_INSERT user_role ON');

		// default user role
		DB::table('user_role')->insert([
			'id'       => "1",
			'user_role_name' => 'Warehouse Custodian',
			'role_status' => '1',
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);
		DB::table('user_role')->insert([
			'id'       => "2",
			'user_role_name' => 'Verifier',
			'role_status' => '1',
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);
		DB::table('user_role')->insert([
			'id'       => "3",
			'user_role_name' => 'General Manager',
			'role_status' => '1',
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);
		DB::table('user_role')->insert([
			'id'       => "4",
			'user_role_name' => 'Administrator',
			'role_status' => '1',
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);

		DB::unprepared('SET IDENTITY_INSERT user_role OFF');
		DB::unprepared('SET IDENTITY_INSERT users ON');

		// dafualt user
		DB::table('users')->insert([
			'id'       => "1",
			'employee_no' => '1000001',
			'firstname'       => "Juan",
			'middlename'       => "Delacruz",
			'lastname'       => "Delacruz",
			'email'   => 'test@yopmail.com',
			'password'   => Hash::make('123123123'),
			'userrole'   => 'superadmin',
			'branch'   => '1',
			'status'   => '1',
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);

		// Warehouse Custodian

		DB::table('users')->insert([
			'id'       => "2",
			'employee_no' => '1000002',
			'firstname'       => "Warehouse Custodian 1",
			'middlename'       => null,
			'lastname'       => "Delacruz",
			'email'   => 'warehouse1@yopmail.com',
			'password'   => Hash::make('123123123'),
			'userrole'   => 'Warehouse Custodian',
			'branch'   => '1',
			'status'   => '1',
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);
		
		DB::table('users')->insert([
			'id'       => "3",
			'employee_no' => '1000002',
			'firstname'       => "Warehouse Custodian 1",
			'middlename'       => null,
			'lastname'       => "Delacruz",
			'email'   => 'warehouse2@yopmail.com',
			'password'   => Hash::make('123123123'),
			'userrole'   => 'Warehouse Custodian',
			'branch'   => '2',
			'status'   => '1',
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);
		
		DB::table('users')->insert([
			'id'       => "4",
			'employee_no' => '1000003',
			'firstname'       => "Warehouse Custodian 2",
			'middlename'       => null,
			'lastname'       => "Delacruz",
			'email'   => 'warehouse3@yopmail.com',
			'password'   => Hash::make('123123123'),
			'userrole'   => 'Warehouse Custodian',
			'branch'   => '3',
			'status'   => '1',
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);

		// verifier
		
		DB::table('users')->insert([
			'id'       => "5",
			'employee_no' => '1000004',
			'firstname'       => "Verifier",
			'middlename'       => null,
			'lastname'       => "Delacruz",
			'email'   => 'verifier@yopmail.com',
			'password'   => Hash::make('123123123'),
			'userrole'   => 'Verifier',
			'branch'   => '1',
			'status'   => '1',
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);

		// general manager
		
		DB::table('users')->insert([
			'id'       => "6",
			'employee_no' => '1000005',
			'firstname'       => "General Manager",
			'middlename'       => null,
			'lastname'       => "Delacruz",
			'email'   => 'general_manager@yopmail.com',
			'password'   => Hash::make('123123123'),
			'userrole'   => 'General Manager',
			'branch'   => '1',
			'status'   => '1',
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),
		]);

		DB::unprepared('SET IDENTITY_INSERT users OFF');
		DB::unprepared('SET IDENTITY_INSERT files ON');

		DB::table('files')->insert([ 'id' => "1", 'filename' => 'Image File 1', 'status'  => '1', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
		DB::table('files')->insert([ 'id' => "2", 'filename' => 'Image File 2', 'status'  => '1', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
		DB::table('files')->insert([ 'id' => "3", 'filename' => 'PDF File 1', 'status'  => '1', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
		DB::table('files')->insert([ 'id' => "4", 'filename' => 'PDF File 2', 'status'  => '1', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
		DB::table('files')->insert([ 'id' => "5", 'filename' => 'Excel File 1', 'status'  => '1', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
		DB::table('files')->insert([ 'id' => "6", 'filename' => 'Excel File 2', 'status'  => '1', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
		DB::table('files')->insert([ 'id' => "7", 'filename' => 'Word File 1', 'status'  => '1', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
		DB::table('files')->insert([ 'id' => "8", 'filename' => 'Word File 2', 'status'  => '1', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);

		DB::unprepared('SET IDENTITY_INSERT files OFF');
		DB::commit();

	}
}
