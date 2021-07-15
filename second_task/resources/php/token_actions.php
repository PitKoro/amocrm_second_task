<?php

use League\OAuth2\Client\Token\AccessToken;

function saveToken($accessToken)
{
    $data = [
        'accessToken' => $accessToken['accessToken'],
        'expires' => $accessToken['expires'],
        'refreshToken' => $accessToken['refreshToken'],
        'baseDomain' => $accessToken['baseDomain'],
    ];

    file_put_contents("../" . $_ENV['TOKEN_FILE'], json_encode($data));
}


function getToken()
{
    $accessToken = json_decode(file_get_contents("../" . $_ENV['TOKEN_FILE']), true);

    return new AccessToken([
        'access_token' => $accessToken['accessToken'],
        'refresh_token' => $accessToken['refreshToken'],
        'expires' => $accessToken['expires'],
        'baseDomain' => $accessToken['baseDomain'],
    ]);
}
