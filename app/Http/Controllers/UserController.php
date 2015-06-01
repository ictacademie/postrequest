<?php  namespace App\Http\Controllers;

use App;
use Auth;
use Hash;
use Validator;
use App\Models\User;
use App\Models\Questions;
use Illuminate\Http\Request;

class UserController extends Controller{
	
	public function getLogin()
	{
		return view('user.login');

	}

	public function postLogin(Request $request)
	{

		$credentials = $request->only('email', 'password');

		$v = Validator::make(
			$credentials,
			[
				'email' => 'required|email', 
				'password' => 'required'
			]
		);

		$v->setAttributeNames(['email' => 'e-mail', 'password' => 'wachtwoord']);

		if($v->fails())
		{
			$request->flash();
			return redirect()->action('UserController@getLogin')->withErrors($v->messages());
		}

		if(Auth::attempt($credentials))
		{
			return redirect()->intended('/');
		}
		
		$request->flash();
		$v->messages()->add('generic', 'Verkeerde wachtwoord en/of email');
		return redirect()->action('UserController@getLogin')->withErrors($v->messages());

	}

	public function getLogout()
	{
		Auth::logout();
		return redirect()->action('UserController@getLogin');
	}

	public function getActivate($key){
		return view('user.activate');
	}

	public function postActivate($key, Request $request){
		$user = User::where('key', $key)->first();
		if($user == NULL){
			return App::abort(404);
		}

		$credentials = $request->only('username', 'password','password2');

		$v = Validator::make(
			$credentials,
			[
				'username' => 'required|unique:users', 
				'password' => 'required|min:8',
				'password2' => 'same:password'
			]
		);

		$v->setAttributeNames(['username' => 'Gebruikersnaam', 'password' => 'Wachtwoord', 'password2' => 'Wachtwoord bevestiging']);

		if($v->fails())
		{
			$request->flash();
			return redirect()->back()->withErrors($v->messages());
		}


		$user->username = $request->username;
		$user->password = Hash::make($request->password);
		$user->key = NULL;
		$user->save();

		return redirect('/');
	}

	public function getProfile(Request $request, $id){
	  $userdata = User::find($id);
	  $questions = $userdata->questions;
	  return view('user/profile')->withuserdata($userdata)->withquestions($questions);
	}

	public function getEdit(){
		$user = User::find(Auth::user()->id);
		return view('user.edit')->with('user', $user);
	}

	public function postEdit(Request $request){
		$user = User::find(Auth::user()->id);
		
		$credentials = $request->only('username','email', 'password');
		$v = Validator::make($credentials, ['username' => "required|unique:users,id,{$user['id']}",'email' => "required|unique:users,id,{$user['id']}",'password' => 'required']);
		$v->setAttributeNames(['username' => 'Gebruikersnaam', 'password' => 'Huidig wachtwoord', 'email' => 'E-mail']);
		if($v->fails())
		{
			$request->flash();
			return redirect()->back()->withErrors($v->messages());
		}
		if(!Auth::validate($request->only('password')))
		{
			$request->flash();
			return redirect()->back()->with("message", "Huidig wachtwoord incorrect.");
		}
		$user->username = $request->input('username');
		$user->email = $request->input('email');
		if(!empty($request->input('newpassword')) || !empty($request->input('newpassword2'))){
			$credentials = $request->only('newpassword','newpassword');
			$v = Validator::make($credentials, ['newpassword' => 'required|min:8', 'newpassword2' => 'same:password']);
			$v->setAttributeNames(['username' => 'Gebruikersnaam', 'password' => 'Huidig wachtwoord', 'email' => 'E-mail']);
			if($v->fails())
			{
				$request->flash();
				return redirect()->back()->withErrors($v->messages());
			}
			$user->password = Hash::make($request->input('newpassword'));
		}
		$user->save();
		$request->flash();
		return redirect()->back()->with("message", "Gebruikers gegevens gewijzigd.");
	}
}