<?php

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessToken;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\LeadModel;
use League\OAuth2\Client\Token\AccessTokenInterface;

include_once '../vendor/autoload.php';
include_once '../vendor/amocrm/amocrm-api-library/examples/error_printer.php';
include_once '../resources/php/token_actions.php';


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/main', function () {
    return view('main');
})->name('main');


Route::post('/validation', [MainController::class, 'validation']);


Route::get('/', function () {
    $apiClient = new AmoCRMApiClient($_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET'], $_ENV['CLIENT_REDIRECT_URI']);
    $state = bin2hex(random_bytes(16));
    session(['oauth2state' => $state]);

    $authorizationUrl = $apiClient->getOAuthClient()->getAuthorizeUrl([
        'state' => $state,
        'mode' => 'post_message',
    ]);

    return redirect()->away($authorizationUrl);
});


Route::get("/form", function (Request $request) {
    $apiClient = new AmoCRMApiClient($_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET'], $_ENV['CLIENT_REDIRECT_URI']);

    $responseFromAuth = $request->all();
    if (isset($responseFromAuth['referer'])) {
        $apiClient->setAccountBaseDomain($responseFromAuth['referer']);
    }
    //exit(dd($responseFromAuth));
    //
    $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($responseFromAuth['code']);

    if (!$accessToken->hasExpired()) {
        saveToken([
            'accessToken' => $accessToken->getToken(),
            'refreshToken' => $accessToken->getRefreshToken(),
            'expires' => $accessToken->getExpires(),
            'baseDomain' => $apiClient->getAccountBaseDomain(),
        ]);
    }

    return redirect()->route('main');
});


Route::get('main/submit', function (Request $request) {
    $apiClient = new AmoCRMApiClient($_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET'], $_ENV['CLIENT_REDIRECT_URI']);
    $accessToken = getToken();

    $apiClient->setAccessToken($accessToken)
        ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
        ->onAccessTokenRefresh(
            function (AccessTokenInterface $accessToken, string $baseDomain) {
                saveToken(
                    [
                        'accessToken' => $accessToken->getToken(),
                        'refreshToken' => $accessToken->getRefreshToken(),
                        'expires' => $accessToken->getExpires(),
                        'baseDomain' => $baseDomain,
                    ]
                );
            }
        );

    $leadsService = $apiClient->leads();
    //Создадим сделку с заполненым бюджетом и привязанными контактами и компанией
    $lead = new LeadModel();
    $name = $request->input('name');
    $lead->setName($name)
        ->setPrice(100000)
        ->setContacts(
            (new ContactsCollection())
                ->add(
                    (new ContactModel())
                        ->setId(6591849)
                )
                ->add(
                    (new ContactModel())
                        ->setId(6490705)
                        ->setIsMain(true)
                )
        )
        ->setCompany(
            (new CompanyModel())
                ->setId(6490685)
        );

    $leadsCollection = new LeadsCollection();
    $leadsCollection->add($lead);

    try {
        $leadsCollection = $leadsService->add($leadsCollection);
    } catch (AmoCRMApiException $e) {
        printError($e);
        die;
    }
    return redirect()->route('success');
})->name('submit');


Route::get('/success', function(){
    return view('success');
})->name('success');