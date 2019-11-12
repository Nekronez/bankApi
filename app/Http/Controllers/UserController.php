<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB as DB;
use Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function createUser(Request $request)
    {
//	var_dump($request);

        $headers = ['Content-Type' => 'application/json', 'charset'=>'utf8'];

	$validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'required|size:11',
	    'password' => 'required',
        ]);

	if ($validator->fails()) {
    	    return response()->json($validator->errors(), 400);
	}

	try{
            $name = $request->name;
            $phone = $request->phone;
	    $password = password_hash($request->password, PASSWORD_DEFAULT );
	Log::info('2Showing user: ');


	    $phoneCheck = DB::table('users')->where('phone', $phone)->count();
	    if($phoneCheck > 0) {
		$data = ["errorMessage" => "This phone is already in the database"];
                return response(json_encode($data), 400, $headers);
	    }
	Log::info('1Showing user: ');

            $id = DB::table('users')->insertGetId(
            	[
      	            'name' => $name,
                    'phone' => $phone,
		    'password' => $password
                ]
	);

            $data = ["id" => $id, "name" => $name, "phone" => $phone];
            return response(json_encode($data), 200, $headers);
        } catch (\Exception $e) {
            $data = ["errorMessage" => "Unknown error: ".$e->getMessage()];
            return response(json_encode($data), 500)//->json($data)
		             ->header('Content-Type', 'application/json; charset=utf-8');//(var_dump($data), 500, $headers);
        }
    }

    public function phpinf()
    {
	return response(phpinfo(), 200);
    }
}
