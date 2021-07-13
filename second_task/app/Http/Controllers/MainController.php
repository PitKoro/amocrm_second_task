<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;




class MainController extends Controller
{
    public function validation(Request $request) {
        //dd($request);
        //Валидация данных
        $request->validate([
            'name' => 'required|alpha',
            'surname' => 'required|alpha',
            'age' => 'required | numeric',
            'phone' => 'required | digits:11'
        ]);

        $access_token = get_access_token();
        dd($access_token);

        return redirect()->route('home');
    }

    
}


function get_access_token() {
    
    $clientId = '948c90a5-7a59-44db-9056-b694e6b8eb69';
    $clientSecret = 'x2OKuCwzFqL8DdeVfgsneiQ9dtJH8OMrtCcowgmNuXn2pB2w1WMxZ6iUkRxd25IC';
    $redirectUri = 'https://example.com';
    $apiClient = new \AmoCRM\Client\AmoCRMApiClient($clientId, $clientSecret, $redirectUri);
    $authorizationUrl = $apiClient->getOAuthClient()->getAuthorizeUrl([
        'state' => $state,
        'mode' => 'post_message', //post_message - редирект произойдет в открытом окне, popup - редирект произойдет в окне родителе
    ]);

    header('Location: ' . $authorizationUrl);
    

    return $access_token;
}