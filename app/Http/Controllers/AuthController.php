<?php

namespace App\Http\Controllers;
use Validator;
use App\User;
use App\SmsSession;
use App\Attempt;
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
     * @param  string   $typeToken
     * @param  int   $smsSessionId
     * @return string
     */
    protected function jwt(User $user, $typeToken, $smsSessionId=null) {
        if($typeToken == 'authorization'){
            $payload = [
                'iss' => "lumen-jwt", // Issuer of the tokenIssuer of the token
                'sub' => $user->id, // Subject of the token
                'typ' => $typeToken,
                'iat' => time(), // Time when JWT was issued
                'exp' => time() + 60*60*4 // Expiration time
            ];
        }else{
            $payload = [
                'iss' => "lumen-jwt", // Issuer of the tokenIssuer of the token
                'sub' => $user->id, // Subject of the token
                'typ' => $typeToken, 
                'sms' => $smsSessionId,
                'iat' => time(), // Time when JWT was issued
            ];
        }
        
        // As you can see we are passing `JWT_SECRET` as the second parameter that will 
        // be used to decode the token in the future.
        return JWT::encode($payload, env('JWT_SECRET'));
    }

    /**
     * Determine the payment system by credit card number.
     *
     * @param  string   $number
     * @return string
     */
    protected function GetCardType($number){
        $types = [
            'electron' => '/^(4026|417500|4405|4508|4844|4913|4917)/',
            'interpayment' => '/^636/',
            'unionpay' => '/^(62|88)/',
            'discover' => '/^6(?:011|4|5)/',
            'maestro' => '/^(50|5[6-9]|6)/',
            'visa' => '/^4/',
            'mastercard' => '/^(5[1-5]|(?:222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720))/', // [2221-2720]
            'amex' => '/^3[47]/',
            'diners' => '/^3(?:0([0-5]|95)|[689])/',
            'jcb' => '/^(?:2131|1800|(?:352[89]|35[3-8][0-9]))/', // 3528-3589
            'mir' => '/^220[0-4]/',
        ];
        foreach($types as $type => $regexp){
            if( preg_match($regexp, $number) ){
                return $type;
            }
        }
    
        return 'undefined';
    }

    /**
     * Determine the payment system by credit card number.
     *
     * @param  string   $digit
     * @return bool
     */
    protected function luhnAlgorithm($digit)
    {
        $number = strrev(preg_replace('/[^\d]/', '', $digit));
        $sum = 0;
        for ($i = 0, $j = strlen($number); $i < $j; $i++) {
            if (($i % 2) == 0) {
                $val = $number[$i];
            } else {
                $val = $number[$i] * 2;
                if ($val > 9)  {
                    $val -= 9;
                }
            }
            $sum += $val;
        }
        return (($sum % 10) === 0);
    }

    /**
     * Authenticate a user and return the token if the provided credentials are correct.
     *
     * @return mixed
     */
    public function getAuthorizationToken() {
        $amountAttempts = 5;
        $blockingTimeMinutes = 2;

        $this->validate($this->request, [
            'pinCode'  => 'required'
        ]);
        
        if($this->request->auth->statusUser->name == 'banned'){
            return response()->json([
                'errorMessage' => 'User banned.'
            ], 423);
        }

        if($this->request->auth->unlock_date > date('Y-m-d H:i:s', time()) ){
            return response()->json([
                'errorMessage' => 'User is locked until '.$this->request->auth->unlock_date
            ], 423);
        }

        $attempt = new Attempt;
        $attempt->user_id = $this->request->auth->id;
        
        if($this->request->auth->pinCode == null){
            return response()->json([
                'errorMessage' => 'User does not have password yet.'
            ], 400);
        }
        
        // Verify the password and generate the token
        if (Hash::check($this->request->input('pinCode'), $this->request->auth->pinCode->value)) {
            $attempt->successfully = true;
            $attempt->save();
            return response()->json([
                'token' => $this->jwt($this->request->auth, "authorization")
            ], 200);
        }

        // Сhange the number of attempts
        $attempt->successfully = false;
        $attempt->save();

        $lastAttempts = Attempt::latest()
                                ->limit($amountAttempts)
                                ->where('user_id', $this->request->auth->id)
                                ->get();

        // Check last attempts
        $blocked=false;
        if(count($lastAttempts) >= $amountAttempts){
            foreach ($lastAttempts as $lastAttempt) {
                if($lastAttempt->successfully == true){
                    $blocked=false;
                    break;
                }
                $blocked=true;
            }
        }

        if($blocked==true){
            $this->request->auth->unlock_date = date('Y-m-d H:i:s', time() + ($blockingTimeMinutes * 60));
            $this->request->auth->save();
            return response()->json([
                'errorMessage' => 'User is locked until '.$this->request->auth->unlock_date
            ], 401);
        }

        return response()->json([
            'errorMessage' => 'Phone or password is wrong.'
        ], 400);
    }

    /**
     * Сheck sms code and return the token if the provided sms code are correct.
     *
     * @return mixed
     */
    public function checkSMSCode() {
        $maxAttempts = 3;
        $smsSession = SmsSession::find($this->request->sms);

        $this->validate($this->request, [
            'code'     => 'required'
        ]);

        if($smsSession->attempts >= $maxAttempts){
            return response()->json([
                'errorMessage' => 'Number of attempts exceeded.'
            ], 423);
        }

        $smsSession->attempts++;

        if($this->request->input('code') != $smsSession->sms_code){
            $smsSession->save();
            return response()->json([
                'errorMessage' => "Invalide code."
            ], 409);
        }

        $smsSession->checked = true;
        $smsSession->save();
        return response()->json(['token' => $this->jwt($this->request->auth, "authorization")], 200);
    }

    /**
     * Send new sms code
     *
     * @return mixed
     */
    public function sendNewSmsCode() {
        $smsSession = SmsSession::find($this->request->sms);

        if(date('Y-m-d H:i:s', strtotime("+1 minutes", strtotime($smsSession->created_at))) > date('Y-m-d H:i:s', time()) ){
            return response()->json([
                'errorMessage' => 'This method is temporarily unavailable until '.date('Y-m-d H:i:s', strtotime("+1 minutes", strtotime($smsSession->created_at))),
            ], 429);
        }

        // Gennerate new sms code
        $smsSession->sms_code = "12345";
        $smsSession->attempts = 0;
        $smsSession->created_at = date('Y-m-d H:i:s', time());

        // Send sms code
        //...

        // If sms was successfully sent
        $smsSession->save();

        return response()->json([], 200);
    }

    /**
     * Find user by value and return token after sent sms
     *
     * @return mixed
     */
    public function verification(){
        $this->validate($this->request, [
            'kind'     => 'required',
            'value'     => 'required',
        ]);

        // Find user by value
        if($this->request->input('kind') == 'phone'){
            if(!preg_match("/^[0-9]{10,10}+$/", $user->phone)){
                return response()->json([
                    'errorMessage' => 'Wrong phone format'
                ], 404);
            }

            $user = User::where('phone', $this->request->input('value'))->first();

            if (!$user) {
                return response()->json([
                    'errorMessage' => 'Phone does not exist.'
                ], 404);
            }
        } else if ($this->request->input('kind') == 'pan'){
            if(GetCardType($this->request->input('value')) == 'undefined' || !luhnAlgorithm()){
                return response()->json([
                    'errorMessage' => 'Wrong pan format'
                ], 404);
            }

            $user = User::join('accounts', 'users.id', '=', 'accounts.user_id')
                        ->join('cards', 'accounts.id', '=', 'cards.account_id')
                        ->where('cards.pan', $this->request->input('value'))->first();

            if (!$user) {
                return response()->json([
                    'errorMessage' => 'PAN does not exist.'
                ], 404);
            }
        } else {
            return response()->json([
                'errorMessage' => "Invalid kind (may be: 'pan' or 'phone')."
            ], 400);
        }

        // Gennerate sms code
        $smsSession = new SmsSession;
        $smsSession->user_id = $user->id;
        $smsSession->sms_code = "12345";
        $smsSession->attempts = 0;
        $smsSession->checked = false;

        // Send sms code
        //...

        // If sms was successfully sent
        $smsSession->save();
        Log::info('$smsSession->id:'.$smsSession->id);

        return response()->json([
            'phone' => "+7*******".substr($user->phone, strlen($user->phone)-3, 3),
            'token' => $this->jwt($user, "session", $smsSession->id)
        ], 200);
    }
}
