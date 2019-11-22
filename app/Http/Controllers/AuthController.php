<?php

namespace App\Http\Controllers;
use Validator;
use App\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller as BaseController;
use Log;

class AuthController extends BaseController
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    private $request;
    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(Request $request) {
        $this->request = $request;
    }
    /**
     * Create a new token.
     *
     * @param  \App\User   $user
     * @return string
     */
    protected function jwt(User $user) {
        $payload = [
            'iss' => "lumen-jwt", // Issuer of the token
            'sub' => $user->id, // Subject of the token
            'iat' => time(), // Time when JWT was issued.
            'exp' => time() + 60*60*24 // Expiration time
        ];

        // As you can see we are passing `JWT_SECRET` as the second parameter that will 
        // be used to decode the token in the future.
        return JWT::encode($payload, env('JWT_SECRET'));
    }
    /**
     * Authenticate a user and return the token if the provided credentials are correct.
     *
     * @param  \App\User   $user 
     * @return mixed
     */
    public function authenticate(User $user) {
        $this->validate($this->request, [
            'kind'     => 'required',
            'value'     => 'required',
            'password'  => 'required'
        ]);

        if($this->request->input('kind') == 'phone'){
            // Find the user by phone
            $user = User::where('phone', $this->request->input('value'))->first();
            if (!$user) {
                return response()->json([
                    'errorMessage' => 'Phone does not exist.'
                ], 404);
            }
        } else if($this->request->kind == 'pan'){
            // Find the user by cardNumber
            $user = User::join('accounts', 'userёs.id', '=', 'accounts.user_id')
                        ->join('cards', 'accounts.id', '=', 'cards.account_id')
                        ->where('cards.pan', $this->request->input('value'))->first();

            if (!$user) {
                return response()->json([
                    'errorMessage' => 'Phone does not exist.'
                ], 404);
            }
        }else{
            return response()->json([
                'errorMessage' => 'Unknown data type.'
            ], 400);
        }
        
        // Verify the password and generate the token
        if (Hash::check($this->request->input('password'), $user->password)) {
            return response()->json([
                'token' => $this->jwt($user)
            ], 200);
        }

        return response()->json([
            'errorMessage' => 'Phone or password is wrong.'
        ], 401);
    }
}
