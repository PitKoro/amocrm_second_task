<?php

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Models\ContactModel;
use AmoCRM\Helpers\EntityTypesInterface;

use AmoCRM\Filters\ContactsFilter;


use Symfony\Component\HttpFoundation\Session\Session;

include_once '../vendor/autoload.php';
include_once '../vendor/amocrm/amocrm-api-library/examples/error_printer.php';
include_once '../resources/php/token_actions.php';
include_once '../resources/php/lead_action.php';
include_once '../resources/php/contact_action.php';


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
    $contactFields = $request->request->all();
    
    $session = new Session();
    $session->start();

    updateToken($apiClient, $accessToken);

    $contactsFilter = new ContactsFilter();
    $contactsFilter->setQuery($contactFields['phone']);
    try {
        $contactsWithFilter = $apiClient->contacts()->get($contactsFilter); // получаем все контакты с введеным номером
    } catch (\AmoCRM\Exceptions\AmoCRMApiNoContentException $e) {
        dd('false');
    }
    
    


    // создаем контакт с всеми полями
    $contact = new ContactModel();
    // Добавляем к контакту имя и фамилию
    $contact->setFirstName($contactFields['name'])->setLastName($contactFields['surname']);

    $customFields = $contact->getCustomFieldsValues(); //Получим коллекцию значений полей контакта

    createPhoneFieldWithValue($customFields, $contactFields['phone']); // создаем поле для телефона и записываем туда значение
    createEmailFieldWithValue($customFields, $contactFields['email']); // Создаем поле для email и записываем туда значение 

    $customFieldsContactsService = $apiClient->customFields(EntityTypesInterface::CONTACTS);

    createGenderField($customFieldsContactsService);
    addValueToGenderField($customFields, $contactFields['gender']);

    createAgeField($customFieldsContactsService);
    addValueToAgeField($customFields, $contactFields['age']);

    $apiClient->contacts()->addOne($contact);// добавляем контакт

    $lead = createLeadWithRandomResponsibleUser($apiClient, $contactFields['name'], $contactFields['surname']); //Создаем сделку и меняем ответственного на случайного

    $apiClient->leads()->addOne($lead); // Добавляем сделку

    $apiClient->contacts()->link($contact, (new LinksCollection())->add($lead)); // Вешаем сделку к контакту

    
// ------------------------Добавляем товары --------------------------------------------------
    addProductsToLead($apiClient, $lead);
    


    return redirect()->route('success');
})->name('submit');


Route::get('/success', function () {
    return view('success');
})->name('success');
