<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB as DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use \App\User;
use \App\Currency;
use \App\Account;
use \App\Card;
use \App\OtherBankCard;
use \App\PinCode;
use \App\PinCodeHistory;
use Log;

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
	    $headers = ['Content-Type' => 'application/json', 'charset'=>'utf8'];

		$validator = Validator::make($request->all(), [
	    	'firstName' => 'required',
	    	'lastName' => 'required',
	    	'middleName' => 'required',
	        'phone' => 'required|size:11',
		    'pinCode' => 'required',
	    ]);

		if ($validator->fails()) {
	        return response()->json($validator->errors(), 400);
		}

		try{
			$user = new User;
	 		$user->first_name = $request->firstName;
	 		$user->last_name = $request->lastName;
	 		$user->middle_name = $request->middleName;
	        $user->phone = $request->phone;
		    $user->pin_code_id = null;

		    $phoneCheck = User::where('phone', $user->phone)
                                ->count();

		    if($phoneCheck > 0) {
				$data = ["errorMessage" => "This phone is already in the database"];
	            return response($data, 409, $headers);
		    }

            $user->save();

            $pinCode = new PinCode;
            $pinCode->user_id = $user->id;
            $pinCode->value = password_hash($request->pinCode, PASSWORD_DEFAULT);
            $pinCode->save();

            $user->pin_code_id = $pinCode->id;
            $user->save();
            
    	    return response($user->toJson(), 201, $headers);
		} catch (\Exception $e) {
	        $data = ["errorMessage" => "Server error: ".$e->getMessage()];
	        return response($data, 500, $headers);
	    }
    }
    
    public function putUserPassword(Request $request)
    {
        $headers = ['Content-Type' => 'application/json', 'charset'=>'utf8'];

		$validator = Validator::make($request->all(), [
		    'pinCode' => 'required|size:5',
        ]);
        
        try{
            $pinCode = $request->auth->pinCode;

            // if(Hash::check($request->pinCode, $pinCode->value)){
            //     return response()->json([
            //         'errorMessage' => 'User has the same password.'
            //     ], 400);
            // }

            $pinCode->value = password_hash($request->pinCode, PASSWORD_DEFAULT );
            $pinCode->save();

            $pinCodeHistory = new PinCodeHistory;
            $pinCodeHistory->value = $pinCode->value;
            $pinCodeHistory->user_id = $pinCode->user_id;
            $pinCodeHistory->save();


            return response()->json([], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Exception $e) {
            $data = ["errorMessage" => "Server error: ".$e->getMessage()];
            return response($data, 500, $headers);
        }
    }

    public function getUserAccounts(Request $request)
    {
        $headers = ['Content-Type' => 'application/json', 'charset'=>'utf8'];
        $userId = $request->auth->id;
        
        try{
            $accounts = Account::with(['currency', 'typeAccount'])
                                ->where('user_id', '=', $userId)
                                ->get();
            
            if(count($accounts)==0){
                return response([], 204, $headers);
            }

            return response()->json($accounts, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Exception $e) {
            $data = ["errorMessage" => "Server error: ".$e->getMessage()];
            return response($data, 500, $headers);
        }
    }

    public function getUserCards(Request $request)
    {
        $headers = ['Content-Type' => 'application/json', 'charset'=>'utf8'];

        $userId = $request->auth->id;

        try{
            $cards = Card::with('statusCard')
                        ->join('accounts', 'cards.account_id', '=', 'accounts.id')
                        ->join('tariffs', 'cards.tariff_id', '=', 'tariffs.id')
                        ->join('cardPictures', 'tariffs.card_picture_id', '=', 'cardPictures.id')
                        ->get(['cards.*', 'url as imageUrl']);

            return response($cards->toJson(), 200, $headers);
        } catch (\Exception $e) {
            $data = ["errorMessage" => "Server error: ".$e->getMessage()];
            return response($data, 500, $headers);
        }
    }

    public function getUserOtherBankCards(Request $request)
    {
        $headers = ['Content-Type' => 'application/json', 'charset'=>'utf8'];

        $userId = $request->auth->id;

        try{
            $otherCards = OtherBankCard::join('otherBankCardPictures', "otherBankCards.other_bank_card_picture_id", "=", "otherBankCardPictures.id")
                                        ->join('cardPictures', "otherBankCardPictures.card_picture_id", "=", "cardPictures.id")
                                        ->where('user_id', $userId)
                                        ->get(['otherBankCards.*', 'url as imageUrl']);

            return response($otherCards->toJson(), 200, $headers);
        } catch (\Exception $e) {
            $data = ["errorMessage" => "Server error: ".$e->getMessage()];
            return response($data, 500, $headers);
        }
    }

    /*
    public function getUser(Request $request, $id)
    {
		$headers = ['Content-Type' => 'application/json', 'charset'=>'utf8'];

		try{
			$user = DB::table('users')
						->select('id', 'name', 'phone')
						->where('id', $id)
						->get();
			if(count($user) == 0) {
				$data = ["errorMessage" => "User is not found"];
     	        return response(json_encode($data), 500, $headers);

			}
            return response(json_encode($user), 200, $headers);
		} catch (\Exception $e) {
            $data = ["errorMessage" => "Unknown error: ".$e->getMessage()];
            return response(json_encode($data), 500, $headers);
        }

    }

	public function putUser(Request $request, $id)
    {
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

			$affected = DB::table('users')
             			->where('id', '=', $id)
            			->update(['name' => $name], ['phone' => $phone], ['password' => $password]);
            if($affected == 0) {
                $data = ["errorMessage" => "User is not found"];
                return response(json_encode($data), 500, $headers);
            }else{
                $data = ["errorMessage" => "User chenged"];
	            return response(json_encode($data), 200, $headers);
			}
        } catch (\Exception $e) {
            $data = ["errorMessage" => "Unknown error: ".$e->getMessage()];
            return response(json_encode($data), 500, $headers);
        }
    }

    public function deleteUser(Request $request, $id)
    {
        $headers = ['Content-Type' => 'application/json', 'charset'=>'utf8'];

        try{
            $affected = DB::table('users')
                        ->where('id', '=', $id)
                        ->delete();
                        //->get();
            if($affected == 0) {
                $data = ["errorMessage" => "User is not found"];
                return response(json_encode($data), 500, $headers);
            }else{
                $data = ["errorMessage" => "User delited"];
                return response(json_encode($data), 200, $headers);
			}
        } catch (\Exception $e) {
            $data = ["errorMessage" => "Unknown error: ".$e->getMessage()];
            return response(json_encode($data), 500, $headers);
        }
    }
    */
    
}
