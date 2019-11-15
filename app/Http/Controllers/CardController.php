<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB as DB;
use Log;
use Illuminate\Support\Facades\Validator;

class CardController extends Controller
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

    public function createCard(Request $request)
    {
		$headers = ['Content-Type' => 'application/json', 'charset'=>'utf8'];

		$validator = Validator::make($request->all(), [
				'name' => 'required',
				'userId' => 'required',
				'paymentSystem' => 'required',
				'type' => 'required',
				'pinCode' => 'required',
			]);

		if ($validator->fails()) {
				return response()->json($validator->errors(), 400);
		}

		try{
	    	$id = rand(1111, 9999)." ".rand(1111, 9999)." ".rand(1111, 9999)." ".rand(1111, 9999);
            $name = $request->name;
            $userId = $request->userId;
            $paymentSystem = $request->paymentSystem;
            $type = $request->type;
            $pinCode = $request->pinCode;
            $balance = 0.0;

            DB::table('card')->insert(
            	[
      	            'id' => $id,
					'name' => $name,
					'balance' => $balance,
            	    'userId' => $userId,
					'paymentSystem' => $paymentSystem,
					'type' => $type,
					'pinCode' => $pinCode,
                ]
	    );

            $data = ["id" => $id, "name" => $name, "balance" => $balance];
            return response(json_encode($data), 200, $headers);
        } catch (\Exception $e) {
            $data = ["errorMessage" => "Unknown error: ".$e->getMessage()];
            return response(json_encode($data), 500)
		             ->header('Content-Type', 'application/json; charset=utf-8');
        }
    }

	public function getUserCard(Request $request, $id, $userId)
	{
	    $headers = ['Content-Type' => 'application/json', 'charset'=>'utf8'];

		try
		{
			$card = DB::table('card')
					->where([
						['userId','=',$userId],
						['id','=',$id],
					])
					->get();
			return response(json_encode($card), 200, $headers);
		}
		catch (\Exception $e)
		{
			$data = ["errorMessage" => "Unknown error: ".$e->getMessage()];
            return response(json_encode($data), 500)
                     ->header('Content-Type', 'application/json; charset=utf-8');

		}
	}
}
