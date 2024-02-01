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
		// default user role
		DB::unprepared('SET IDENTITY_INSERT user_role ON');
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
		
		// dafualt user
		DB::unprepared('SET IDENTITY_INSERT users ON');
			DB::table('users')->insert([
				'id'       => "1",
				'employee_no' => '0',
				'firstname'       => "Admin",
				'middlename'       => "",
				'lastname'       => "Account",
				'email'   => 'admin@suerte.com',
				'password'   => Hash::make('admin.suerte'),
				'userrole'   => 'Administrator',
				'branch'   => '1',
				'status'   => '1',
				'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),
			]);
		DB::unprepared('SET IDENTITY_INSERT users OFF');

		// dafualt nationality
		DB::unprepared('SET IDENTITY_INSERT nationality ON');
			DB::table('nationality')->insert([ 'id' => "1", 'name' => 'FILIPINO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "2", 'name' => 'ANDORRA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "3", 'name' => 'U.A.E', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "4", 'name' => 'AFGHANISTAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "5", 'name' => 'ANTIGUA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "6", 'name' => 'ANGUILLA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "7", 'name' => 'ALBANIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "8", 'name' => 'ARMENIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "9", 'name' => 'NETH ANT.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "10", 'name' => 'ANGOLA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "11", 'name' => 'ANTARCTICA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "12", 'name' => 'ARGENTINA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "13", 'name' => 'AMERICAN SAMOA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "14", 'name' => 'AUSTRIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "15", 'name' => 'AUSTRALIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "16", 'name' => 'ARUBA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "17", 'name' => 'ALAND ISLANDS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "18", 'name' => 'AZERBAIJAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "19", 'name' => 'BOSNIA-HERZ', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "20", 'name' => 'BARBADOS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "21", 'name' => 'BANGLADESH', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "22", 'name' => 'BELGIUM', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "23", 'name' => 'BURKINA FASO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "24", 'name' => 'BULGARIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "25", 'name' => 'BAHRAIN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "26", 'name' => 'BURUNDI', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "27", 'name' => 'BENIN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "28", 'name' => 'SAINT BARTHEL', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "29", 'name' => 'BERMUDA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "30", 'name' => 'BRUNEI', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "31", 'name' => 'BOLIVIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "32", 'name' => 'BRAZIL', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "33", 'name' => 'BAHAMAS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "34", 'name' => 'BHUTAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "35", 'name' => 'BOUVET ISLAND', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "36", 'name' => 'BOTSWANA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "37", 'name' => 'BELARUS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "38", 'name' => 'BELIZE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "39", 'name' => 'CANADA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "40", 'name' => 'COCOS ISLANDS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "41", 'name' => 'CONGO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "42", 'name' => 'CENT AFR', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "43", 'name' => 'CONGO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "44", 'name' => 'SWITZERLAND', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "45", 'name' => 'IVORY', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "46", 'name' => 'COOK ISLANDS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "47", 'name' => 'CHILE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "48", 'name' => 'CAMEROON', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "49", 'name' => 'CHINA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "50", 'name' => 'COLUMBIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "51", 'name' => 'COSTA RICA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "52", 'name' => 'CUBA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "53", 'name' => 'CAPE VERDE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "54", 'name' => 'CHRISTMAS IS.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "55", 'name' => 'CYPRUS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "56", 'name' => 'CZECH REPUBLIC', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "57", 'name' => 'GERMANY', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "58", 'name' => 'DJIBOUTI', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "59", 'name' => 'DENMARK', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "60", 'name' => 'DOMINICA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "61", 'name' => 'DOMINICAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "62", 'name' => 'ALGERIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "63", 'name' => 'ECUADOR', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "64", 'name' => 'ESTONIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "65", 'name' => 'EGYPT', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "66", 'name' => 'WESTERN SAHARA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "67", 'name' => 'ERITREA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "68", 'name' => 'SPAIN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "69", 'name' => 'ETHIOPIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "70", 'name' => 'EUR COUNTRIES', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "71", 'name' => 'FINLAND', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "72", 'name' => 'FIJI', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "73", 'name' => 'FALKLAND IS.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "74", 'name' => 'MICRONESIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "75", 'name' => 'FAROE ISLANDS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "76", 'name' => 'FRANCE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "77", 'name' => 'GABON', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "78", 'name' => 'G.B.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "79", 'name' => 'GRENADA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "80", 'name' => 'GEORGIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "81", 'name' => 'FRENCH GUIANA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "82", 'name' => 'CHANNEL ISLANDS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "83", 'name' => 'GHANA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "84", 'name' => 'GIBRALTAR', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "85", 'name' => 'GREENLAND', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "86", 'name' => 'GAMBIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "87", 'name' => 'GUINEA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "88", 'name' => 'GOLD', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "89", 'name' => 'GUADELOUPE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "90", 'name' => 'EQU. GUINEA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "91", 'name' => 'GREECE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "92", 'name' => 'S GEORGIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "93", 'name' => 'GUATEMALA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "94", 'name' => 'GUAM', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "95", 'name' => 'GUINEA-BISSAU', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "96", 'name' => 'GUYANA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "97", 'name' => 'H.K.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "98", 'name' => 'HEARD .MCDONALD', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "99", 'name' => 'HONDURAS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "100", 'name' => 'CROATIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "101", 'name' => 'HAITI', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "102", 'name' => 'HUNGARY', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "103", 'name' => 'INDONESIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "104", 'name' => 'IRELAND', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "105", 'name' => 'ISRAEL', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "106", 'name' => 'ISLE OF MAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "107", 'name' => 'INDIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "108", 'name' => 'BR IND. OC. TER', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "109", 'name' => 'IRAQ', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "110", 'name' => 'IRAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "111", 'name' => 'ICELAND', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "112", 'name' => 'ITALY', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "113", 'name' => 'JERSEY', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "114", 'name' => 'JAMAICA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "115", 'name' => 'JORDAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "116", 'name' => 'JAPAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "117", 'name' => 'KENYA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "118", 'name' => 'KYRGYZSTAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "119", 'name' => 'CAMBODIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "120", 'name' => 'KIRIBATI', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "121", 'name' => 'COMORO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "122", 'name' => 'ST KITTS .NEVIS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "123", 'name' => 'KOREA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "124", 'name' => 'KOREA, REP. OF', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "125", 'name' => 'KUWAIT', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "126", 'name' => 'CAYMAN ISLANDS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "127", 'name' => 'KAZAKSTAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "128", 'name' => 'LAO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "129", 'name' => 'LEBANON', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "130", 'name' => 'ST. LUCIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "131", 'name' => 'LIECHT', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "132", 'name' => 'SRI LANKA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "133", 'name' => 'LIBERIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "134", 'name' => 'LESOTHO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "135", 'name' => 'LITHUANIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "136", 'name' => 'LUXEMBOURG', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "137", 'name' => 'LATVIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "138", 'name' => 'LIBYA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "139", 'name' => 'MOROCCO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "140", 'name' => 'MONACO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "141", 'name' => 'MOLDOVA, REP OF', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "142", 'name' => 'MONTENEGRO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "143", 'name' => 'SAINT MARTIN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "144", 'name' => 'MADAGASCAR', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "145", 'name' => 'MARSHALL IS.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "146", 'name' => 'MACEDONIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "147", 'name' => 'MALI', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "148", 'name' => 'MYANMAR', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "149", 'name' => 'MONGOLIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "150", 'name' => 'MACAU', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "151", 'name' => 'N. MARIANA IS.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "152", 'name' => 'MARTINIQUE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "153", 'name' => 'MAURITANIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "154", 'name' => 'MONSERRAT', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "155", 'name' => 'MALTA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "156", 'name' => 'MAURITIUS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "157", 'name' => 'MALDIVES', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "158", 'name' => 'MALAWI', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "159", 'name' => 'MEXICO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "160", 'name' => 'MALAYSIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "161", 'name' => 'MOZAMBIQUE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "162", 'name' => 'NAMIBIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "163", 'name' => 'NEW CALEDONIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "164", 'name' => 'NIGER', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "165", 'name' => 'NORFOLK ISLAND', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "166", 'name' => 'NIGERIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "167", 'name' => 'NICARAGUA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "168", 'name' => 'NETHERLANDS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "169", 'name' => 'NORWAY', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "170", 'name' => 'NEPAL', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "171", 'name' => 'NAURU', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "172", 'name' => 'NIUE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "173", 'name' => 'N.Z.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "174", 'name' => 'OMAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "175", 'name' => 'PANAMA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "176", 'name' => 'PERU', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "177", 'name' => 'FR. POLYNESIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "178", 'name' => 'PAP. NEW GUINEA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "179", 'name' => 'PLATINUM', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "180", 'name' => 'PAKISTAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "181", 'name' => 'POLAND', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "182", 'name' => 'ST. PIERRE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "183", 'name' => 'PITCAIRN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "184", 'name' => 'PUERTO RICO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "185", 'name' => 'PORTUGAL', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "186", 'name' => 'PALAU', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "187", 'name' => 'PARAGUAY', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "188", 'name' => 'QATAR', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "189", 'name' => 'REUNION', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "190", 'name' => 'ROMANIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "191", 'name' => 'SERBIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "192", 'name' => 'RUSSIAN FED', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "193", 'name' => 'RWANDA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "194", 'name' => 'SAUDI ARABIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "195", 'name' => 'SOLOMON ISLANDS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "196", 'name' => 'SEYCHELLES', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "197", 'name' => 'SUDAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "198", 'name' => 'SWEDEN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "199", 'name' => 'SINGAPORE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "200", 'name' => 'ST. HELENA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "201", 'name' => 'SLOVENIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "202", 'name' => 'SVALBARD', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "203", 'name' => 'SLOVAKIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "204", 'name' => 'SIERRA LEONE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "205", 'name' => 'SAN MARINO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "206", 'name' => 'SENEGAL', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "207", 'name' => 'SOMALIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "208", 'name' => 'SURINAME', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "209", 'name' => 'SILVER', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "210", 'name' => 'SAO TOME', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "211", 'name' => 'EL SALVADOR', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "212", 'name' => 'SYRIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "213", 'name' => 'SWAZILAND', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "214", 'name' => 'TURKS . CAICOS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "215", 'name' => 'CHAD', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "216", 'name' => 'FR. S. TERRIT.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "217", 'name' => 'TOGO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "218", 'name' => 'THAILAND', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "219", 'name' => 'TAJIKISTAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "220", 'name' => 'TOKELAU', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "221", 'name' => 'TURKMENISTAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "222", 'name' => 'TUNISIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "223", 'name' => 'TONGA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "224", 'name' => 'EAST TIMOR', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "225", 'name' => 'TURKEY', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "226", 'name' => 'TRINIDAD TOBAGO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "227", 'name' => 'TUVALU', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "228", 'name' => 'TAIWAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "229", 'name' => 'TANZANIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "230", 'name' => 'UKRAINE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "231", 'name' => 'UGANDA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "232", 'name' => 'US MINOR OUT IS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "233", 'name' => 'USA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "234", 'name' => 'URUGUAY', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "235", 'name' => 'UZBEKISTAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "236", 'name' => 'VATICAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "237", 'name' => 'ST. VINCENT', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "238", 'name' => 'VENEZUALA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "239", 'name' => 'VIRGIN ISLANDS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "240", 'name' => 'VIRGIN ISLANDS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "241", 'name' => 'VIETNAM', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "242", 'name' => 'VANUATU', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "243", 'name' => 'WALLIS .FUTUNA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "244", 'name' => 'SAMOA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "245", 'name' => 'XAU AND XAG', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "246", 'name' => 'EUROPEAN UNIT', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "247", 'name' => 'EAST CARIBBEAN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "248", 'name' => 'SPECIAL DRAWING', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "249", 'name' => 'EUROPA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "250", 'name' => 'GOLD', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "251", 'name' => 'WEST AFRICA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "252", 'name' => 'XPD AND XPT', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "253", 'name' => 'SILVER', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "254", 'name' => 'WORLDWIDE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "255", 'name' => 'YEMEN', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "256", 'name' => 'MAYOTTE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "257", 'name' => 'YUGOSLAVIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "258", 'name' => 'SOUTH AFR', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "259", 'name' => 'ZAMBIA', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('nationality')->insert([ 'id' => "260", 'name' => 'ZIMBABWE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
		DB::unprepared('SET IDENTITY_INSERT nationality OFF');

		// default source of income
		DB::unprepared('SET IDENTITY_INSERT source_of_income ON');
			DB::table('source_of_income')->insert(['id' => "1", 'source' => 'GOVERNMENT EMPLOYEE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('source_of_income')->insert(['id' => "2", 'source' => 'PRIVATE EMPLOYEE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('source_of_income')->insert(['id' => "3", 'source' => 'SELF-EMPLOYED', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('source_of_income')->insert(['id' => "4", 'source' => 'BUSINESS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('source_of_income')->insert(['id' => "5", 'source' => 'PENSION', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
			DB::table('source_of_income')->insert(['id' => "6", 'source' => 'REMITTANCE', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ]);
		DB::unprepared('SET IDENTITY_INSERT source_of_income OFF');

		DB::commit();

	}
}
