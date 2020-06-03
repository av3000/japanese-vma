<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\CustomList;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    const KNOWNRADICALS = 1;
    const KNOWNKANJIS = 2;
    const KNOWNWORDS = 3;
    const KNOWNSENTENCES = 4;

    public function register(Request $request)
    {
    	$validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:6|max:20|confirmed'
        ]);

        if($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password'))
        ]);

        // rads
        $radsList = new CustomList;
        $radsList->user_id = $user->id;
        $radsList->publicity = 0;
        $radsList->type = self::KNOWNRADICALS;
        $radsList->title = "My Known Radicals";
        $radsList->save();
        // kanjis
        $kanjiList = new CustomList;
        $kanjiList->user_id = $user->id;
        $kanjiList->publicity = 0;
        $kanjiList->type = self::KNOWNKANJIS;
        $kanjiList->title = "My Known Kanjis";
        $kanjiList->save();
        // words
        $wordList = new CustomList;
        $wordList->user_id = $user->id;
        $wordList->publicity = 0;
        $wordList->type = self::KNOWNWORDS;
        $wordList->title = "My Known Words";
        $wordList->save();
        // sentences
        $sentenceList = new CustomList;
        $sentenceList->user_id = $user->id;
        $sentenceList->publicity = 0;
        $sentenceList->type = self::KNOWNSENTENCES;
        $sentenceList->title = "My Known Sentences";
        $sentenceList->save();

     	$accessToken = $user->createToken('authToken');

     	return response()->json(['user'=>$user, 'accessToken'=>$accessToken->accessToken]);
    }

    public function login(Request $request) 
    {
    	$loginData = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->get()->first();

        if(!auth()->attempt($loginData)) 
        {
        	return response()->json(['error' => ['login' => 'Invalid email/password']], 400);
        }
        
    	$accessToken = auth()->user()->createToken('authToken');

     	return response()->json(['user'=>auth()->user(), 'accessToken'=>$accessToken->accessToken]);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
  
    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
    // https://medium.com/modulr/create-api-authentication-with-passport-of-laravel-5-6-1dc2d400a7f

}
