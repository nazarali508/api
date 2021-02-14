<?php
namespace App\Controllers;
use CodeIgniter\Validation\Exceptions\ValidationException;
use Config\Services;
use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use ReflectionException;

use CodeIgniter\Controller;
use http\Url;
use App\Libraries\Datatables;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

require APPPATH . '/ThirdParty/firebase/php-jwt/src/BeforeValidException.php';
require APPPATH . '/ThirdParty/firebase/php-jwt/src/ExpiredException.php';
require APPPATH . '/ThirdParty/firebase/php-jwt/src/SignatureInvalidException.php';
require APPPATH . '/ThirdParty/firebase/php-jwt/src/JWT.php';

use \Firebase\JWT\JWT;

// headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control");

 	
class Api extends ResourceController
{
    
   use ResponseTrait;
   protected $helpers = ['url','form','html','text','file','dir','date','time','customfunction'];
   
   public function getRequestInput(IncomingRequest $request){
    $input = $request->getPost();
    if (empty($input)) {
        //convert request body to associative array
        $input = json_decode($request->getBody(), true);
    }
    return $input;
}

   public function validateRequest($input, array $rules, array $messages =[]){
    $this->validator = Services::Validation()->setRules($rules);
    // If you replace the $rules array with the name of the group
    if (is_string($rules)) {
        $validation = config('Validation');

        // If the rule wasn't found in the \Config\Validation, we
        // should throw an exception so the developer can find it.
        if (!isset($validation->$rules)) {
            throw ValidationException::forRuleNotFound($rules);
        }

        // If no error message is defined, use the error message in the Config\Validation file
        if (!$messages) {
            $errorName = $rules . '_errors';
            $messages = $validation->$errorName ?? [];
        }

        $rules = $validation->$rules;
    }
    return $this->validator->setRules($rules, $messages)->run($input);
}
   
    public function createUser()
    {
        $userModel = new UserModel();
        $reg_code = generate_rand();
       
	$rules = [
                'username'         => 'trim|regex_match[/^[a-z0-9_-]+$/]|max_length[20]|required|is_unique[users.username]',
                'name'         => 'required|regex_match[/^([a-z ])+$/i]|max_length[35]',
                'email'            => 'required|valid_email|is_unique[users.email]',
                'mobile'            => 'required|numeric|min_length[10]|is_unique[users.mobile]',
                'password'         => 'required'
                
            ];

	    
	    $input = $this->request->getPost();
	    if (empty($input)) {
		//convert request body to associative array
		$input = json_decode($request->getBody(), true);
		
	    }
	    $input['reg_code']=$reg_code;
	    $input['reg_status']='Live';
	    $input['status']='Suspend';
	    $input['password']=password_hash($this->request->getVar('password'),PASSWORD_BCRYPT);
	    
	   
	    
           if (!$this->validateRequest($input, $rules)) {
	        $response['status']=400;
		$response['error']='Validation Error';
  	    	$response['messages']=$this->validator->getErrors();
               
            }else{
	    if ($userModel->save($input)) {
    
		$response = [
		    'status' => 200,
		    "error" => FALSE,
		    'messages' => 'User created',
		];
	    } else {
    
		$response = [
		    'status' => 500,
		    "error" => TRUE,
		    'messages' => 'Failed to create',
		];
	    }
	    }
        return $this->respondCreated($response);
    }

    private function getKey()
    {

        return "my_application_secret";
    }

    public function validateUser()
    {
        $userModel = new UserModel();

	
	 $userEmail= $this->request->getVar("email");
	 $userdata=$userModel->ORwhere(array('username'=>$userEmail,'email'=>$userEmail,'mobile'=>$userEmail))->first();
		

        if (!empty($userdata)) {
	    
            if (password_verify($this->request->getVar("password"), $userdata['password'])) {

               
	        if($userdata['reg_status'] =='Live'){
		
		$response = [
                    'status' => 500,
                    'error' => FALSE,
                    'messages' => lang('app.Please verify your email / mobile no.')
                           ];
		
		
		}
	       else if($userdata['status'] !='Active'){
		$response = [
			'status' => 500,
			'error' => FALSE,
			'messages' => lang('app.User have been block from system, please contact system admin')
		    ];
		    
		}else{
	       
	        $key = $this->getKey();

                $iat = time();
                $nbf = $iat + 10;
                $exp = $iat + 3600;

                $payload = array(
                    "iss" => "The_claim",
                    "aud" => "The_Aud",
                    "iat" => $iat,
                    "nbf" => $nbf,
                    "exp" => $exp,
                    "data" => $userdata,
                );

                $token = JWT::encode($payload, $key);

                $response = [
                    'status' => 200,
                    'error' => FALSE,
                    'messages' => 'User logged In successfully',
                    'token' => $token
                ];
                
		 $userLog = [
			   'id'               => $userdata['id'],
			   'last_accessed_ip' => $this->request->getIPAddress(),
			   'last_login'       => date('Y-m-d H:i:s'),
			   'logins'           => $userdata['logins']+1
		       ];
		       $userModel->save($userLog);
		
		}
		return $this->respondCreated($response);
            } else {

                $response = [
                    'status' => 500,
                    'error' => TRUE,
                    'messages' => 'Incorrect details'
                ];
                return $this->respondCreated($response);
            }
        } else {
            $response = [
                'status' => 500,
                'error' => TRUE,
                'messages' => 'User not found'
            ];
            return $this->respondCreated($response);
        }
    }

    public function userDetails()
    {
        $key = $this->getKey();
        $authHeader = $this->request->getHeader("Authorization");
        $authHeader = $authHeader->getValue();
        $token = $authHeader;

        try {
            $decoded = JWT::decode($token, $key, array("HS256"));

            if ($decoded) {

                $response = [
                    'status' => 200,
                    'error' => FALSE,
                    'messages' => 'User details',
                    'data' => $decoded
                ];
                return $this->respondCreated($response);
            }
        } catch (Exception $ex) {
            $response = [
                'status' => 401,
                'error' => TRUE,
                'messages' => 'Access denied'
            ];
            return $this->respondCreated($response);
        }
    }
}
