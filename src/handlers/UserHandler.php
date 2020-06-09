<?php
namespace src\handlers;
use \src\models\User;
use \src\models\UserRelation;
use \src\handlers\PostHandler;

class UserHandler{
    
    public static function checkLogin(){
        if(!empty($_SESSION['token'])){
            $token = $_SESSION['token'];
            
            $data = User::select()->where('token', $token)->one();
                        
            if($data && count($data) > 0) {
                $loggedUser = new User();
                $loggedUser->id = $data['id'];
                $loggedUser->name = $data['name'];
                $loggedUser->avatar = $data['avatar'];
                
                return $loggedUser;
            }
    }
    return false;
    }
    
    public static function verifyLogin($email, $password) {
        $user = User::select()->where('email', $email)->one();
        
        if($user){
            if(password_verify($password, $user['password'])){
                $token = md5(time().rand(0,9999).time());
                
                User::update()
                        ->set('token', $token)
                        ->where('email', $email)
                    ->execute();
                return $token;
            }
        }
        
        return false;
    }

    public function idExists($id) {
        $user = User::select()->where('id', $id)->one();
        return $user ? true : false;
    }
    
    public function emailExists($email) {
        $user = User::select()->where('email', $email)->one();
        return $user ? true : false;
    }

    /* [$full = false] significa que se for solicidado um perfil completo
       o $full irá retornar true (verdadeiro) e iremos a partir disso informar
       o perfil completo para o usuário, caso contrário o $full permanecerá como falso
       sendo enviado apenas os dados básicos. */
    public function getUser($id, $full = false){
        $data = User::select()->where('id', $id)->one();
        if($data){
            $user = new User();
            $user->id = $data['id'];
            $user->name = $data['name'];
            $user->birthdate = $data['birthdate'];
            $user->city = $data['city'];
            $user->work = $data['work'];
            $user->avatar = $data['avatar'];
            $user->cover = $data['cover'];
            
            if($full){
                $user->followers = [];
                $user->following = [];
                $user->photos = [];

                // followers
                $followers = UserRelation::select()->where('user_to', $id)->get();
                foreach($followers as $follower){
                    $userData = User::select()->where('id', $follower['user_from'])->one();
                    $newUser = new User();
                    $newUser->id = $userData['id'];
                    $newUser->name = $userData['name'];
                    $newUser->avatar = $userData['avatar'];

                    $user->followers[] = $newUser;
                }
                // following
                $following = UserRelation::select()->where('user_from', $id)->get();
                foreach($following as $follower){
                    $userData = User::select()->where('id', $follower['user_to'])->one();
                    $newUser = new User();
                    $newUser->id = $userData['id'];
                    $newUser->name = $userData['name'];
                    $newUser->avatar = $userData['avatar'];

                    $user->following[] = $newUser;
                }
                // photos
                $user->photos = PostHandler::getPhotosFrom($id);
            }

            return $user;
        }
        return false;
    }
    
    public function addUser($name,$email,$password,$birthdate){
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $token = md5(time(). rand(0,9999). time());
        User::insert([
            'name' => $name,
            'email' => $email,
            'password' => $hash,
            'birthdate' => $birthdate,
            'token' => $token
        ])->execute();
        return $token;
    }

    public static function isFollowing($from, $to){
        $data = UserRelation::select()
            ->where('user_from', $from)
            ->where('user_to', $to)
        ->one();
        
        if($data){
            return true;
        }
        return false;
    }
}