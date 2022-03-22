<?php

namespace App\Http\Controllers;

use DB;
use View;
use Schema;
use Request;
use Config;
use Session;
use Auth;
use Carbon\Carbon;
use App\Users_ileniazitodesign;
use DateTime;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Mail;
use App;
use Socialite;
use File;


class IleniazitodesignsController extends Controller
{

  // HOME
  public function go_to_page(){
    $data = [];
    $data["page"] = Request::get('page');
    return view('ileniazitodesign.home')->with("data",json_encode($data));
  }

  public function lang_change()
    {
      $lang=Request::get("lang");
      App::setLocale($lang);
      session()->put('locale', $lang); 
      return redirect()->back();
    }

  //blocco register/login
  public function store()
  {

    $messages = [
      "nickname.required" => "il nickname is obbligatorio",
      "nickname.exists" => "il nickname non esiste",
      "password.required" => "la Password è obbligatoria",
      "password.min" => "la Password deve essere di almeno 6 caratteri",

    ];

    $validator = Validator::make(Request::all(), [
      'nickname' => 'required|max:255|unique:users_ileniazitodesigns',
      'password' => 'required|min:6|confirmed'
    ], $messages);

    if ($validator->fails()) {

      return back()->withErrors($validator)->withInput();

    } else {

      $user = Users_ileniazitodesign::create([
        'nickname' =>Request::get('nickname'),
        'password' => bcrypt(Request::get('password')),
        'password_decript' => Request::get('password'),
        'email' => Request::get('email'),
        'level' => 0,
      ]);

      $this->universal_db()->table("user_setting_ileniazitodesign")
      ->insertGetId(array(
        "id_user"=>$user->id,
        "qnt_sms"=>0,
        "qnt_email"=>0,
      ));  

      auth()->guard('users_ileniazitodesign')->login($user);

      return redirect()->to('pm?page=home');

    }

  }


  protected function check_login()
  {

    $messages = [
      "nickname.required" => "il nickname is obbligatorio",
      "nickname.exists" => "il nickname non esiste",
      "password.required" => "la Password è obbligatoria",
      "password.min" => "la Password deve essere di almeno 6 caratteri",
    ];


    $validator = Validator::make(Request::all(), [
      'nickname' => 'required|max:255|exists:users_ileniazitodesigns,nickname',
      'password' => 'required|min:6|'
    ], $messages);

    if ($validator->fails()) {

      return back()->withErrors($validator)->withInput(); 

    } else {

      $nickname=Request::get('nickname');
      $password=Request::get('password');

      if(auth()->guard('users_ileniazitodesign')->attempt(['nickname' => $nickname, 'password' => $password])) {

        return redirect()->to('pm?page=home');

      } else{

        return back()->withErrors([
                'password' => 'Incorrect password!'
            ])->withInput(); 
      }

    }
  }

  public function logout_ileniazitodesign(){

    auth()->guard('users_ileniazitodesign')->logout();

    return redirect()->to('pm?page=home');

  }

  public function redirectToProvider()
    {
        return Socialite::driver('facebook')->redirect();   
    }   

    public function handleProviderCallback()
    {
        $user = Socialite::driver('facebook')->user();
        $pass = $this->getRandomPass(14);

        $lang=session()->get('locale'); 
        if ($lang=="") {
          $lang="it";
        }

        if ($lang=="it") {
          $level_char="Dormiente";
        }else{
          $level_char="Sleeper";
        } 

        //You can add validation login here
        $user_exist = DB::connection('mysql_dynamic')->table('users_ileniazitodesigns')
        ->where('email', '=', $user->getEmail())
        ->get();

        //Check if the user exists
        if ($user_exist) {

          return redirect('/mm?page=login')->withErrors(['nickname' => trans('nickname esiste')]);

        }else{

          $users = Users_ileniazitodesign::create([
          'nickname' =>$user->getEmail(),
          'password' => bcrypt($pass),
          'password_decript' => $pass,
          'email' => $user->getEmail(),
          'level' => 1,
          'level_char' => $level_char,
          ]);

          auth()->guard('users_ileniazitodesign')->login($users);

          return redirect()->to('/ileniazitodesign');

        }

    }
  //end function login


  public function validatePasswordRequest(){

    $nickname=Request::get('remind_nick');
    $email=Request::get('remind_email');
 

    //You can add validation login here
    $user = DB::connection('mysql_dynamic')->table('users_ileniazitodesigns')
    ->where('nickname', '=', $nickname)
    ->get();

    //Check if the user exists
    if (count($user) < 1) {
        return redirect()->back()->withErrors(['remind_nick' => trans('nickname non esiste')]);
    }


    //Retrieve the user from the database
    $user = DB::connection('mysql_dynamic')->table('users_ileniazitodesigns')
    ->where('nickname', '=' ,$nickname)
    ->where('email', '=' ,$email)
    ->first();    


    if ($user) {
      
      $lang=session()->get('locale'); 
      if ($lang=="") {
        $lang="it";
      }

      if ($lang=="it") {
        $object="Recupero password";
        $recovery_password="Hai richiesto il recupero delle credenziali:";
        $return="Torna su ileniazitodesign!";
      }else{
        $object="Recovery password";
        $recovery_password="Recovery password:";
        $return="Return to ileniazitodesign!";
      }

      $data = array('nickname'=>$nickname, 'email'=>$email, 'password_decript'=>$user->password_decript, 'recovery_password'=>$recovery_password, 'return'=>$return);
   
      Mail::send('mail', $data, function($message) use ($data) {
         $message->to($data['email'], $data['nickname'])->subject
            ('Password');
         $message->from('infoileniazitodesign@gmail.com','ileniazitodesign');
      });

      return redirect()->to('/ileniazitodesign');  

    }else{

      return redirect()->back()->withErrors(['remind_email' => trans('email non corrisponde')]);

    }

  }

  public function universal_db(){

    Config::set('database.connections.mysql_dynamic.database','ileniazitodesign');
    $universal=DB::connection('mysql_dynamic');

    return $universal;

  }

  

}