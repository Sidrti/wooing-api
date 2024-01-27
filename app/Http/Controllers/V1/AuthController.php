<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\user;
use App\Models\verification_token;
use App\Models\model_stream_detail;
use App\Models\model_social_account;
use App\Models\model_doc_detail;
use App\Models\user_detail;
use App\Models\categorie;
use App\Models\user_identity_verification;
use Illuminate\Support\Facades\Hash;
use App\Services\SumsubService;
use Mail;
use File;

class AuthController extends Controller
{
    protected $key = '123456';

    /* Login & Logout */

    public function login(Request $request)
    {
        $fields = $request->validate([
            'identifier' => 'required',
            'password' => 'required_if:loginType,1',
            'loginType' => 'required'
        ]);
       
        $user = user::where('email', $fields['identifier'])->first();

        if ($user) 
        { 
            $luxury_club_privilege = Helper::fetchLuxuryClubPrivilegeByUserId($user->id);
            $details = user_detail::select("*")
                ->where('user_id', '=', $user->id)
                ->get();

            $token = $user->createToken($this->key)->plainTextToken;

            if ($user->role !== 2) {
                $details = null;
            }
            $response = [
                'status_code' => 1,
                'data' => ['user' => $user, 'details' => $details, 'token' => $token, 'luxury_club_privilege' => $luxury_club_privilege],
                'message' => 'Login success'
            ];
            if ($request->loginType == 1) {
                if (!Hash::check($fields['password'], $user->password)) {
                    return response([
                        'status_code' => 2,
                        'data' => [],
                        'message' => 'Incorrect password !',
                    ], 200);
                    
                } else if($user->status == 10) {
                    return response([
                        'status_code' => 10,
                        'data' => [],
                        'message' => 'Account is banned by Sweetiecam. Contact support !',
                    ], 200);
                }
                else if($user->status == 11) {
                    $user_identity_verification = user_identity_verification::select('applicant_id')
                    ->where('user_id',$user->id)
                    ->orderBy('id','desc')
                    ->first();

                    return response([
                        'status_code' => 11,
                        'data' => ['applicant_id' => isset($user_identity_verification) ? $user_identity_verification->applicant_id : ''],
                        'message' => 'Account is temporary disabled due to identity verification process',
                    ], 200);
                } else if($user->status == 12) {
                    return response([
                        'status_code' => 12,
                        'data' => [],
                        'message' => 'Account is permanently disabled. Please contact support',
                    ], 200);
                }
                else if($user->status == 13) {
                    return response([
                        'status_code' => 13,
                        'data' => [],
                        'message' => 'Account verification in process. Please try after some time',
                    ], 200);
                }
                else {
                        return response($response, 200);
                }
            } else if ($request->loginType == 2) {
                if (!$user) {
                    return response([
                        'status_code' => 2,
                        'data' => [],
                        'message' => 'User does not exists!!',
                    ], 200);
                } 
                else if($user->status == 10) {
                    return response([
                        'status_code' => 10,
                        'data' => [],
                        'message' => 'Account is banned by Sweetiecam. Contact support !',
                    ], 200);
                } else {
                    if($user->status == 11) {
                        return response([
                            'status_code' => 11,
                            'data' => [],
                            'message' => 'Account is temporary disabled due to identity verification process',
                        ], 200);
                    } else if($user->status == 12) {
                        return response([
                            'status_code' => 12,
                            'data' => [],
                            'message' => 'Account is permanently disabled. Please contact support',
                        ], 200);
                    }
                    else if($user->status == 13) {
                        return response([
                            'status_code' => 13,
                            'data' => [],
                            'message' => 'Account verification in process. Please try after some time',
                        ], 200);
                    }
                    else {
                        $response = [
                            'status_code' => 1,
                            'data' => ['user' => $user, 'details' => $details, 'token' => $token, 'luxury_club_privilege' => $luxury_club_privilege],
                            'message' => 'Login success'
                        ];
    
                        return response($response, 201);
                    }
                }
            }
        } else {
            return response([
                'status_code' => 2,
                'data' => [],
                'message' => 'Please register first !',
            ], 200);
        }
    }
    public function logout()
    {
        auth()->user()->tokens()->delete();
        return response([
            'status_code' => 1,
            'data' => [],
            'message' => 'Tokens Revoked'
        ], 200);
    }

    /* ---Login & Logout --- */

     /* Registration */
    public function register(Request $request)
    {
        $feild = $request->validate([
            'username' => 'required_if:role,1,3|string|max:255',
            'email' => 'required_if:role,1,3|string|email',
            'role' => 'required',
            'interests' => 'required_if:role,2|string',
            'dob' => 'required_if:role,2',
            'language' => 'required_if:role,2|array',
            'language.*' => 'string', // Validate each element in the array as a string
            'bodyType' => 'required_if:role,2|string',
            'ethnicity' => 'required_if:role,2|string',
            'profilePath' => 'required_if:role,2',
            'coverPath' => 'required_if:role,2',
            'idProofPathFront' => 'required_if:role,2',
            'idProofPathBack' => 'required_if:role,2'

        ]);
        if ($request->role == 2) {

            $data = verification_token::select("*")
                ->where('token', '=', $request->tokenId)
                ->where('email_verified', '=', 1)
                ->get();

            if (count($data) < 1) {
                $response = [
                    'status_code' => 2,
                    'data' => [],
                    'message' => 'Email is not verfied!!'
                ];
                return response($response, 200);
            }
            $check = $this->userExists($data[0]['email']);
            if ($check) {
                $response = [
                    'status_code' => 2,
                    'data' => [],
                    'message' => 'Email already exists!!'
                ];
                return response($response, 200);
            }
            $token = substr(str_shuffle("0123456789"), 0, 8);

            $user = user::create([
                'username' => $data[0]['username'],
                'email' => $data[0]['email'],
                'password' => $data[0]['password'],
                'role' => 2,
                'status' => 13   //Account verification in process
            ]);
            $token1 = $user->createToken($this->key)->plainTextToken;
            $path = public_path() . '/uploads/model_documents';
            if (!File::exists($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }

            $extension = $request->file('profilePath')->getClientOriginalExtension();
            $filename = 'model_' . $user->id . '_' . 'profile_' . $token;
            $profilefileNameToStore = $filename  . '.' . $extension;
            $profilePath = 'uploads/model_documents/' . $profilefileNameToStore;

            $coverfileNameToStore = 'model_' . $user->id . '_' . 'cover_' . $token . '.' . $request->file('coverPath')->getClientOriginalExtension();
            $idprooffileNameToStoreFront = 'model_' . $user->id . '_' . 'idproof_front' . $token . '.' . $request->file('idProofPathFront')->getClientOriginalExtension();
            $idprooffileNameToStoreBack = 'model_' . $user->id . '_' . 'idproof_back' . $token . '.' . $request->file('idProofPathBack')->getClientOriginalExtension();
            $coverPath = 'uploads/model_documents/' . $coverfileNameToStore;
            $idproofPathFront = 'uploads/model_documents/' . $idprooffileNameToStoreFront;
            $idproofPathBack = 'uploads/model_documents/' . $idprooffileNameToStoreBack;

            $request->file('profilePath')->move(public_path('uploads/model_documents'), $profilefileNameToStore);
            $request->file('coverPath')->move(public_path('uploads/model_documents'), $coverfileNameToStore);
            $request->file('idProofPathFront')->move(public_path('uploads/model_documents'), $idprooffileNameToStoreFront);
            $request->file('idProofPathBack')->move(public_path('uploads/model_documents'), $idprooffileNameToStoreBack);


            $user_detail = new user_detail;
            // $user_detail->role = $request->role;
            $user_detail->user_id = $user->id;
            $user_detail->interests = $request->interests;
            $user_detail->dob = $request->dob;
            $user_detail->language = json_encode($request->language);
            $user_detail->body_type = $request->bodyType;
            $user_detail->ethnicity = $request->ethnicity;
            $user_detail->specifics = $request->specifics;
            $user_detail->hair_color = $request->hairColor;
            $user_detail->eye_color = $request->eyeColor;
            $user_detail->about = $request->about;
            $user_detail->specifics = $request->specifics;
            $user_detail->profile_path = $profilePath;
            $user_detail->cover_path = $coverPath;
            $user_detail->about = $request->about;
            $user_detail->save();

            $model_stream_detail = new model_stream_detail;
            $model_stream_detail->model_id =  $user->id;
            $model_stream_detail->private = isset($request->private) ? $request->private : 0;
            $model_stream_detail->exclusive_private = isset($request->exclusivePrivate) ? $request->exclusivePrivate : 0;
            $model_stream_detail->spying = isset($request->spying) ? $request->spying : 0;
            $model_stream_detail->group_show = isset($request->groupShow) ? $request->groupShow : 0 ;
            $model_stream_detail->ticket_show = isset($request->ticketShow) ? $request->ticketShow : 0;
            $model_stream_detail->save();

            $model_doc_detail = new model_doc_detail;
            $model_doc_detail->model_id =  $user->id;
            $model_doc_detail->country = $request->country;
            $model_doc_detail->id_type = $request->idType;
            $model_doc_detail->gender = $request->gender;
            $model_doc_detail->city = $request->city;
            $model_doc_detail->address = $request->address;
            $model_doc_detail->id_proof_path_front = $idproofPathFront;
            $model_doc_detail->id_proof_path_back = $idproofPathBack;
            $model_doc_detail->save();

            $model_social_account = new model_social_account;
            $model_social_account->model_id = $user->id;;
            $model_social_account->snapchat_url = null;
            $model_social_account->instagram_url = null;
            $model_social_account->onlyfans_url = null;
            $model_social_account->twitter_url = null;

            $model_social_account->save();

            $this->submitSumSubUserVerification($user->id,$user->email,$model_doc_detail->country, $user_detail->dob,$model_doc_detail->id_type,public_path($idproofPathFront),$user->username, $model_doc_detail->gender);


            $luxury_club_privilege = Helper::fetchLuxuryClubPrivilegeByUserId($user->id);

            $response = [
                'status_code' => 10,
                'data' => ['user' => $user, 'details' => $user_detail, 'token' => $token1,'luxury_club_privilege' => $luxury_club_privilege],
                'message' => 'Verification process inprogress. We will complete your verification process with 24 hours. Till then, sit back and relax !'
            ];
            return response($response, 201);
        } else if ($request->role == 1) {  //Google Register

            $check = $this->userExists($request->email);
            if ($check) {
                $response = [
                    'status_code' => 2,
                    'data' => [],
                    'message' => 'User already exists !'
                ];
                return $this->response($response);
            }
            if ($request->loginType == 1) {
                $pass = Hash::make($request->password);
            } elseif ($request->loginType == 2) {
                $pass = Hash::make($request->email);
            }
            $user = user::create([
                'username' => $request->username,
                'email' => $request->email,
                'password' => $pass,
                'role' => $request->role,
                'login_type' => $request->loginType,
            ]);

            $token = $user->createToken($this->key)->plainTextToken;
            $response = [
                'status_code' => 1,
                'data' => ['user' => $user, 'token' => $token],
                'message' => 'User created sucessfully!!'
            ];
            return response($response, 201);
        }
    }
    public function resubmitIdentityVerification(Request $request)
    {
        $feild = $request->validate([
            'applicantId' => 'required|string',
            'country' => 'required|string',
            'idType' => 'required|string',
            'idProofPath' => 'required|mimes:jpeg,jpg,bmp,png,mp4,mov,ogg,qt | max:20000',
        ]);

        $data = user_identity_verification::select("user_identity_verifications.user_id","user_identity_verifications.review_status","users.email")
            ->join('users','users.id','=','user_identity_verifications.user_id')
            ->where('user_identity_verifications.applicant_id', '=', $request->applicantId)
            ->orderBy('user_identity_verifications.id','desc')
            ->first();

        if (!isset($data->user_id) || $data->review_status != 'RETRY') {
            $response = [
                'status_code' => 2,
                'data' => [],
                'message' => 'Incorrect applicant id'
            ];
            return response($response, 200);
        }
        $token = substr(str_shuffle("0123456789"), 0, 8);

        $path = public_path() . '/uploads/model_documents';
        if (!File::exists($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        $idprooffileNameToStore = 'model_' . $data->user_id . '_' . 'idproof_' . $token . '.' . $request->file('idProofPath')->getClientOriginalExtension();
        $idproofPath = 'uploads/model_documents/' . $idprooffileNameToStore;
        $request->file('idProofPath')->move(public_path('uploads/model_documents'), $idprooffileNameToStore);

        model_doc_detail::where("model_id",  $data->user_id)
        ->update([
            'id_prood_path' =>  $idproofPath,
            'id_type' => $request->idType,
            'country' => $request->country
        ]);

        $this->reSubmitSumSubUserVerification($request->applicantId, $request->country,$request->idType,public_path($idproofPath));

        $response = [
            'status_code' => 10,
            'message' => 'Verification process inprogress. We will complete your verification process with 24 hours. Till then, sit back and relax !'
        ];
        return response($response, 201);
    
    }

    public function sendVerificationEmail(Request $request)
    {
        $feild = $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|string|email',
            'password' => 'nullable|min:6',
            'role' => 'required'
        ]);
        $check = $this->userExists($request->email);
        if ($check) {
            $response = [
                'status_code' => 2,
                'data' => ['email' => $feild['email']],
                'message' => 'User already registered'
            ];
            return response($response, 200);
        }
        $token = base64_encode($feild['email']);
        $pass = Hash::make($feild['password']);
        $random = substr(str_shuffle("0123456789"), 0, 8);
        $store_token = verification_token::create([
            'token' => $token . $random,
            'email' => $feild['email'],
            'password' => $pass,
            'username' => $feild['username'],
            'role' => $feild['role'],
            'type' => 1
        ]);

        if ($store_token) {
            $url = config('app')['API_URL'] . '/email/verify/' . 'id=' . $token . $random . '/email=' . $feild['email'];
            
            $subject = "Sweetiecam model email verification";
            $data = [
                'subject' => $subject,
                'url' => $url,
            ];
        
            $body = view('email.model_registration', $data)->render();
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: hi@sweetiecam.com' . "\r\n"; 

            mail($feild['email'], $subject, $body, $headers);

            $response = [
                'status_code' => 1,
                'data' => ['email' => $feild['email']],
                'message' => 'Mail has been send for confirmation!!',
                'url' => $url
            ];
            return response($response, 201);
        } else {
            $response = [
                'status_code' => 2,
                'data' => ['email' => $feild['email']],
                'message' => 'Request Failed!!'
            ];
            return response($response, 201);
        }
    }
    public function verifyToken($token, $email)
    {
        $verification_token_data = verification_token::select("*")
            ->where('token', '=', $token)
            ->where('email', '=', $email)
            ->get();

        $check = $this->userExists($email);
        if ($check) {
            $response = [
                'status_code' => 2,
                'data' => [],
                'message' => 'Email already exists'
            ];
            return $this->response($response);
        }
        if (count($verification_token_data) > 0 && $verification_token_data[0]->role == 1) {
            $user = user::create([
                'username' => $verification_token_data[0]['username'],
                'email' => $verification_token_data[0]['email'],
                'password' => $verification_token_data[0]['password'],
                'role' => $verification_token_data[0]->role,
            ]);
            return redirect()->away(config('app')['WEB_URL'].'/login')->header('token', $token);
        } else if (count($verification_token_data) > 0 && $verification_token_data[0]->role == 2) {
            verification_token::where('token', $token)
                ->update([
                    'email_verified' => true
                ]);

            return redirect()->away(config('app')['WEB_URL'].'/model-registration-stages?tokenId=' . $token . '&' . 'el=2');
        } else {
            $response = [
                'status_code' => 2,
                'data' => ['email' => $email],
                'message' => 'Mail verification failed!!'
            ];
            return response($response);
            //return redirect()->away('http://localhost:8080/login');
        }
    }

     /* ---Registration --- */

    /* Forget Password API */
    public function sendVerificationEmailForgetPassword(Request $request)
    {
        $feild = $request->validate([
            'email' => 'required|string|email',
        ]);
        $check = $this->userExists($request->email);
        if (!$check) {
            $response = [
                'status_code' => 2,
                'data' => ['email' => $feild['email']],
                'message' => 'This email doesnt exists, please register !'
            ];
            return response($response, 200);
        }
        $token = base64_encode($feild['email']);
        $random = substr(str_shuffle("0123456789"), 0, 8);
        $token_id = $token.$random;
        $store_token = verification_token::create([
            'token' => $token_id,
            'email' => $feild['email'],
            'type' => 2
        ]);
        if ($store_token) {
            $url = config('app')['API_URL']  . '/forget-password/verify/' . 'id=' . $token_id . '/email=' . $feild['email'];

            $subject = "Sweetiecam user forget password";
            $data = [
                'subject' => $subject,
                'url' => $url,
            ];
        
            $body = view('email.user_forget_password', $data)->render();
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: hi@sweetiecam.com' . "\r\n"; 

            mail($feild['email'], $subject, $body, $headers);

            $response = [
                'status_code' => 1,
                'data' => ['email' => $feild['email']],
                'message' => 'Mail has been send for confirmation!!',
                'url' => $url
            ];
            return response($response, 200);
        } else {
            $response = [
                'status_code' => 2,
                'data' => ['email' => $feild['email']],
                'message' => 'Request Failed!!'
            ];
            return response($response, 201);
        }
    }
    public function forgetPasswordVerifyToken($token,$email)
    {
        $verification_token_data = verification_token::select("*")
            ->where('token', '=', $token)
            ->where('email', '=', $email)
            ->get();

        if (count($verification_token_data) > 0) {
            verification_token::where('token', $token)
                ->update([
                    'email_verified' => true
                ]);
            return redirect()->away(config('app')['WEB_URL'] .'/change-password?token='.$token);
        }
        else {
            $response = [
                'status_code' => 2,
                'message' => 'Mail verification failed!!'
            ];
            return response($response);
            //return redirect()->away('http://localhost:8080/login');
        }
    }
    public function changePassword(Request $request)
    {
        $feild = $request->validate([
            'token' => 'required|string|max:255',
            'password' => 'required|min:6',
        ]);

        $data = verification_token::select("*")
            ->where('token', '=', $feild['token'])
            ->where('email_verified', '=', 1)
            ->first();

        $hashed_password = Hash::make($feild['password']);

        $update = user::where("email", $data->email)
        ->update([
            'password' => $hashed_password,
        ]);
        
        if($update)
        {
            $response = [
                'status_code' => 1,
                'message' => 'Password changed !!'
            ];
        }
        else
        {
            $response = [
                'status_code' => 2,
                'message' => 'Error occured while changing password'
            ];
        }

        return response($response, 201);
    }

     /* ---Forget Password API--- */
    /* Authenticated Change Password */

     public function authChangePassword(Request $request)
     {
        $feild = $request->validate([
            'password' => 'required|min:6',
        ]);
        $user_id = auth()->user()->id;
        $hashed_password = Hash::make($feild['password']);

        $update = user::where("id", $user_id)
        ->update([
            'password' => $hashed_password,
        ]);
        
        if($update)
        {
            $response = [
                'status_code' => 1,
                'message' => 'Password changed !!'
            ];
        }
        else
        {
            $response = [
                'status_code' => 2,
                'message' => 'Error occured while changing password'
            ];
        }
        return response($response, 201);
     }
     /* --- Authenticated Change Password--- */

    public function fetchCategories()
    {

        $category = categorie::select("*")
            ->where('parent_category_id', '=', -1)
            ->get();

        for ($i = 0; $i < count($category); $i++) {
            $sub_category = categorie::select("id", "name")
                ->where('parent_category_id', '=', $category[$i]->id)
                ->get();

            for ($j = 0; $j < count($sub_category); $j++) {
                $data2[$category[$i]->name][$j]['id'] = $sub_category[$j]->id;
                $data2[$category[$i]->name][$j]['name'] = $sub_category[$j]->name;
            }
        }
        $response = [
            'status_code' => 1,
            'data' => ['category_data' => $data2],
            'message' => 'Data fetched successfully!!'
        ];
        return response($response, 200);
    }
    public function response($message)
    {
        $response = [
            'status_code' => 1,
            'data' => [],
            'message' => $message
        ];

        return response($response, 200);
    }

    public function userExists($email)
    {
        $exists = user::select("*")
            ->where('email', '=', $email)
            ->exists();
        if ($exists) {
            return 1;
        } else 0;
    }
    public function submitSumSubUserVerification($user_id,$email,$country,$dob,$id_type,$image_path,$username,$gender)
    {
        $level_name = 'sweetiecam_model_age';
        $sumsub = new SumsubService('sbx:Wo2y4DqwHyx15FTYEnCCEoJS.XRRdw2E9VaI8Yyyu7nNlIjqJDBk8rGE0', 'NOGHdXHlCH9qdf3vhEm6ttIMpIedor7r');
        $applicant_id = $sumsub->createApplicant($user_id, $level_name, $email,$country,$username, $gender);
        $image_id = $sumsub->addDocument($applicant_id,$image_path,
            ['idDocType' => $id_type, 'country' => $country,'dob' => $dob]);
        $request_check = $sumsub->requestApplicationCheck($applicant_id);

        return 1;
    }
    public function reSubmitSumSubUserVerification($applicant_id,$country,$id_type,$image_path)
    {
        $sumsub = new SumsubService('sbx:Wo2y4DqwHyx15FTYEnCCEoJS.XRRdw2E9VaI8Yyyu7nNlIjqJDBk8rGE0', 'NOGHdXHlCH9qdf3vhEm6ttIMpIedor7r');
        $sumsub->addDocument($applicant_id,$image_path,
            ['idDocType' => $id_type, 'country' => $country]
        );
        $sumsub->requestApplicationCheck($applicant_id);

        return 1;
    }
}
