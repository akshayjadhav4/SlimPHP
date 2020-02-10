<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

require '../includes/DbOperations.php';

$app = new \Slim\App;




    /*
     endpoint : create user
     param : email , passowrd , name , school
     method : POST;
    */

    //API Call
    $app->post('/createUser', function(Request $request , Response $response){
        
        if(!haveEmptyParam(array('email', 'password', 'name', 'school'),$request,$response)){
            
            $request_data =  $request->getParsedBody();

            $email = $request_data['email'];
            $password = $request_data['password'];
            $name = $request_data['name'];
            $school= $request_data['school'];

            $hash_password =password_hash($password,PASSWORD_DEFAULT);

            $db = new DbOperations;
            
            $result = $db->createUser($email,$hash_password,$name,$school);

            if ($result == USER_CREATED) {
                $message = array();
                $message['error'] = false;
                $message['message'] = 'User Created Successfully...';

                $response->write(json_encode($message));

                return $response->withHeader('Content-type','application/json')->withStatus(201);

            } else if($result == USER_FAILURE) {
                
                $message = array();
                $message['error'] = true;
                $message['message'] = 'Something Went Wrong...';

                $response->write(json_encode($message));

                return $response->withHeader('Content-type','application/json')->withStatus(422);

            } else if($result == USER_EXISTS){
                $message = array();
                $message['error'] = true;
                $message['message'] = 'User Already Exists...';

                $response->write(json_encode($message));

                return $response->withHeader('Content-type','application/json')->withStatus(422);

            }
            

        }
        return $response->withHeader('Content-type','application/json')->withStatus(422);

    });


    /* 
    endpoint : userLogin
    param : email , password
    method : POST
    */
    $app->POST('/userLogin', function(Request $request , Response $response)
    {
        
        if (! haveEmptyParam(array('email','password'),$request,$response)) {
            
            $request_data =  $request->getParsedBody();
            $email = $request_data['email'];
            $password = $request_data['password'];

            $db = new DbOperations;
            $result = $db->userLogin($email,$password);

            if($result == USER_AUTHENTICATED){
                $user = $db->getUserByEmail($email);
                $response_data = array();
                $response_data['error'] = false;
                $response_data['message'] = 'Login Successful...';
                $response_data['user'] = $user;
                $response->write(json_encode($response_data));
                return $response->withHeader('Content-type','application/json')->withStatus(200);
            }else if ($result == USER_NOT_FOUND ){
                $response_data = array();
                $response_data['error'] = true;
                $response_data['message'] = 'User does not exists.';
                $response->write(json_encode($response_data));
                return $response->withHeader('Content-type','application/json')->withStatus(200);
            }else if ($result == USER_PASSWORD_DO_NOT_MATCH){
                $response_data = array();
                $response_data['error'] = true;
                $response_data['message'] = 'Invalid Credentials.';
                $response->write(json_encode($response_data));
                return $response->withHeader('Content-type','application/json')->withStatus(200);
            }
        }
    });


    /* 
    endpoint : allUsers
    param : 
    method : GET
    */

    $app->get('/allUsers', function(Request $request , Response $response){
        $db = new DbOperations();

        $users = $db->getAllUsers();

        $response_data = array();
        $response_data['error'] = false;
        $response_data['users'] = $users;

        $response->write(json_encode($response_data));

        return $response->withHeader('Content-type','application/json')->withStatus(200);

    });


    /* 
    endpoint : updateUser
    param : id
    method : PUT
    */

    $app->PUT('/updateUser/{id}',function(Request $request , Response $response , array $args){
        $id = $args['id'];
        if(!haveEmptyParam(array('email','name', 'school'),$request,$response)){
            $request_data = $request->getParsedBody();
            $email = $request_data['email'];
            $name = $request_data['name'];
            $school= $request_data['school'];
            

            $db = new DbOperations;

            if($db->updateUser($id,$email,$name,$school)){
                $response_data = array();
                $response_data['error'] = false;
                $response_data['message'] = 'User Updated Successfully...';
                $user = $db->getUserByEmail($email);
                $response_data['user'] = $user;
                $response->write(json_encode($response_data));

                return $response->withHeader('Content-type','application/json')->withStatus(200);
            }else{
                $response_data = array();
                $response_data['error'] = true;
                $response_data['message'] = 'Something Went Wrong...';
                $user = $db->getUserByEmail($email);
                $response_data['user'] = $user;
                $response->write(json_encode($response_data));

                return $response->withHeader('Content-type','application/json')->withStatus(200);

            }

        }
        return $response->withHeader('Content-type','application/json')->withStatus(200);

    });


    /* 
    endpoint : updatepassword
    param : 
    method : PUT
    */
    $app->PUT('/updatePassword',function(Request $request , Response $response){

        if(!haveEmptyParam(array('currentPassword', 'newPassword', 'email'),$request,$response)){
            $request_data = $request->getParsedBody();
            $currentPassword = $request_data['currentPassword'];
            $newPassword = $request_data['newPassword'];
            $email = $request_data['email'];
            
            $db = new DbOperations;

            $result = $db->updatePassword($currentPassword, $newPassword ,$email);
            if($result == USER_PASSWORD_CHANGED){
                $response_data = array();
                $response_data['error'] = false;
                $response_data['message'] = 'Pasword Changed.';
                $response->write(json_encode($response_data));
                return $response->withHeader('Content-type','application/json')->withStatus(200);

            }else if($result == USER_PASSWORD_NOT_MATCHED){
                $response_data = array();
                $response_data['error'] = true;
                $response_data['message'] = 'You have entered wrong password.';
                $response->write(json_encode($response_data));
                return $response->withHeader('Content-type','application/json')->withStatus(200);

            }else if($result == USER_PASSWORD_NOT_CHANGED){
                $response_data = array();
                $response_data['error'] = true;
                $response_data['message'] = 'Something went wrong...';
                $response->write(json_encode($response_data));
                return $response->withHeader('Content-type','application/json')->withStatus(200);

            }
        }
        return $response->withHeader('Content-type','application/json')->withStatus(422);

    });


    /* 
    endpoint : deleteUser
    param : id
    method : delete
    */

    $app->delete('/deleteUser/{id}',function(Request $request , Response $response , array $args){
        $id = $args['id'];

        $db = new DbOperations;
        $response_data = array();

        if($db->deleteUser($id)){
            $response_data['error'] = false;
            $response_data['message'] = 'User Deleted...';
        }else{
            $response_data['error'] = true;
            $response_data['message'] = 'Something went wrong.';
        }
        $response->write(json_encode($response_data));
        return $response->withHeader('Content-type','application/json')->withStatus(422);

    });

    function haveEmptyParam($required_params , $request, $response){
        $error = false;
        $error_param = '';

        $request_params = $request->getParsedBody();

        foreach($required_params as $param){
            if(!isset($request_params[$param]) || strlen($request_params[$param]) <= 0){
                $error = true;
                $error_param .= $param . ' ,';
            }
        }

        if($error){
            $error_detail = array();
            $error_detail['error'] = true;
            $error_detail['message'] = 'Required Parameter ' . substr($error_param, 0 ,-2) . ' are missing.';
            $response->write(json_encode($error_detail));
        }

        return $error;
    }

$app->run();