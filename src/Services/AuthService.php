<?php
namespace Src\Services;
require  '././vendor/autoload.php';
use GuzzleHttp\Client;
use Src\Entities\User;
use GuzzleHttp\Exception\ClientException;

class AuthService {

    private $Client;
    private $Config;

    public function __construct(){
        $this->Config = file_get_contents('globalConfig.json');
        $this->Config = json_decode($this->Config);
        $this->Client =  new Client(['base_uri' => $this->Config->AuthService->url ,'curl' => array(CURLOPT_SSL_VERIFYPEER => false)]);
    }

    public function logIn($username , $password){
        try {
            $response = $this->Client->post('/api/login',  ['json' => ['username' => $username, 'password' => $password]]);
        } catch (ClientException $exeption) {
            $response = $exeption->getResponse();
        }
        
        $user = $this->loginHandler($response);
        return $user;
    }

    public function loginHandler($response){
        if (intval($response->getStatusCode()) == 200 ) {
            $response = json_decode($response->getBody()->read(1024));
            try {
                $user = $this->Client->get('/api/user/me', ['headers' => $this->makeHeaders($response)]);
            } catch(ClientException $exeption) {
                $user = $exeption->getResponse();
            }
            
            if (intval($user->getStatusCode()) == 200) {
                $user = json_decode($user->getBody()->read(1024));
                $user = new User($user->id , $user->email , $user->username , $user->roles , $response->token , $response->refresh_token );
                if ($user instanceof User) {
                    return $user;
                }
            }else{
               $error = [
                    'code' => intval($user->getStatusCode()), 
                    'message' => $user->getBody()->getContents()
               ];
               return $error;
            }
        }else{
            $error = [
                'code' => intval($response->getStatusCode()),
                'message' => $response->getBody()->getContents()
            ];
            return $error;
        }
    }

    public function makeHeaders($response){
        $headers = ['Authorization' => 'Bearer ' . $response->token, 'Accept' => 'application/json'];
        return $headers;
    }

    public function logOut(){

    }

    public function refresh($refresh_token){
        try {
            $token = $this->Client->get('/api/user/refresh', ['json' => ['refresh_token' => $refresh_token]]);
        } catch (ClientException $exeption) {
            $token = $exeption->getResponse();
        }
        if (intval($token->getStatusCode()) != 200) {
            return false;
        }
        $token = json_decode($token->getBody()->read(1024));
        return $token->refresh_token;
    }

    public function guard($user){
        if (!$user instanceof User) {
            $user = '';
           return false ;
        }
        return true ;
    }

    
    protected function errorHandler(){

    }

}