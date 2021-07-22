<?php

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use AmoCRM\Client\AmoCRMApiClient;

use AmoCRM\Collections\LinksCollection;
use AmoCRM\Models\ContactModel;
use AmoCRM\Helpers\EntityTypesInterface;

use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Filters\CustomersFilter;

use AmoCRM\Models\Customers\CustomerModel;
use AmoCRM\Exceptions\AmoCRMApiException;

use Symfony\Component\HttpFoundation\Session\Session;

use php\MyClasses\Contact;
use php\MyClasses\Lead;
use php\MyClasses\Helper;

include_once '../vendor/autoload.php';
include_once '../vendor/amocrm/amocrm-api-library/examples/error_printer.php';
include_once '../resources/php/token_actions.php';




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

    // проверка на дубли по номеру телефона
    $contactsFilter = new ContactsFilter();
    $contactsFilter->setQuery($contactFields['phone']);
    try {
        $contactsWithFilter = $apiClient->contacts()->get($contactsFilter); // получаем все контакты с введеным номером
        $contactsWithFilterArray = $contactsWithFilter->toArray();
    } catch (\AmoCRM\Exceptions\AmoCRMApiNoContentException $e) {
    }

    if (isset($contactsWithFilterArray)) {
        $leadsFilter = new LeadsFilter();
        $leadsFilter->setQuery($contactFields['phone']);
        $leadsWithFilter = $apiClient->leads()->get($leadsFilter);
        try {
            $customersFilter = new CustomersFilter();
            $customersFilter->setQuery($contactFields['phone']);
            $customersWithFilter = $apiClient->customers()->get($customersFilter);
            $customersWithFilterArray = $customersWithFilter->toArray();
        } catch (\AmoCRM\Exceptions\AmoCRMApiNoContentException $e) {
        }

        if (isset($customersWithFilterArray)) return redirect()->route('success'); // если покупатель уже существует, то редирект

        $leadWithWonStatus = $leadsWithFilter->first()->setStatusId((int)$_ENV['SUCCESS_STATUS_ID']); // меняем statusId  на 142
        $apiClient->leads()->updateOne($leadWithWonStatus); // сохраняем изменения

        $customer = new CustomerModel(); //Создадим покупателя
        $customer->setName("Покупатель {$contactsWithFilterArray[0]['name']}")
            ->setNextDate(strtotime("+10 day"));

        try {
            $customer = $apiClient->customers()->addOne($customer);
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }
        //Привяжем контакт к созданному покупателю
        try {
            $contact = $apiClient->contacts()->getOne($contactsWithFilterArray[0]['id']);
            // $contact->setIsMain(false);
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        try {
            $apiClient->customers()->link($customer, (new LinksCollection())->add($contact));
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        return redirect()->route('success');
    }

    $contact = new Contact($apiClient, $contactFields);
    $apiClient->contacts()->addOne($contact); // добавляем контакт







    // $lead = createLeadWithRandomResponsibleUser($apiClient, $contactFields['name'], $contactFields['surname']); //Создаем сделку и меняем ответственного на случайного
    $lead = new Lead($apiClient, $contactFields['name']);
    $apiClient->leads()->addOne($lead); // Добавляем сделку

    $apiClient->contacts()->link($contact, (new LinksCollection())->add($lead)); // Привязываем сделку к контакту
    $lead->addProductsToLead(); // добавляем товары к сделке
    $lead->createTaskToLead();

    return redirect()->route('success');
})->name('submit');


Route::get('/success', function () {
    return view('success');
})->name('success');
