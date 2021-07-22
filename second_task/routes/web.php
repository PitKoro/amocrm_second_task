<?php

use App\Http\Controllers\MainController;

use App\MyClasses\Contact;
use App\MyClasses\Lead;
use App\MyClasses\Token;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use AmoCRM\Client\AmoCRMApiClient;

use AmoCRM\Collections\LinksCollection;

use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Filters\CustomersFilter;

use AmoCRM\Models\Customers\CustomerModel;
use AmoCRM\Exceptions\AmoCRMApiException;

use Symfony\Component\HttpFoundation\Session\Session;




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
    $responseFromAuth = $request->all();
    $paramsNotFound = [];
    if(empty($responseFromAuth['code'])) {
        $paramsNotFound[] = 'code';
    }
    if (empty($responseFromAuth['referer'])) {
        $paramsNotFound[] = 'referer';
    }
    if (empty($responseFromAuth['client_id'])) {
        $paramsNotFound[] = 'client_id';
    }

    if(!empty($paramsNotFound)) {
        $error_msg = [
            "ERROR"=>[
                "code"=>"400",
                "msg"=>"bad request"
            ],
            "params"=>[
                "notFound"=> $paramsNotFound
            ]
        ];

        dd($error_msg);
    }
   
    $apiClient = new AmoCRMApiClient($_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET'], $_ENV['CLIENT_REDIRECT_URI']);

    
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
    $contactFields = $request->all();
    $paramsNotFound = [];
    if (empty($contactFields['name'])) {
        $paramsNotFound[] = 'name';
    }
    if (empty($contactFields['surname'])) {
        $paramsNotFound[] = 'surname';
    }
    if (empty($contactFields['age'])) {
        $paramsNotFound[] = 'age';
    }
    if (empty($contactFields['phone'])) {
        $paramsNotFound[] = 'phone';
    }
    if (empty($contactFields['email'])) {
        $paramsNotFound[] = 'email';
    }

    if (!empty($paramsNotFound)) {
        $error_msg = [
            "ERROR" => [
                "code" => "400",
                "msg" => "bad request"
            ],
            "params" => [
                "notFound" => $paramsNotFound
            ]
        ];

        dd($error_msg);
    }

    $apiClient = new AmoCRMApiClient($_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET'], $_ENV['CLIENT_REDIRECT_URI']);
    $tokenAction = new Token();
    $accessToken = $tokenAction->getToken();
    // $contactFields = $request->request->all();

    $session = new Session();
    $session->start();

    $tokenAction->updateToken($apiClient, $accessToken);

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

    $lead = new Lead($apiClient, $contactFields['name']);
    $apiClient->leads()->addOne($lead); // Добавляем сделку
    $apiClient->contacts()->link($contact, (new LinksCollection())->add($lead)); // Привязываем сделку к контакту
    $lead->addProductsToLead(); // добавляем товары к сделке
    $lead->createTaskToLead(); // Создаем задачу в сделке

    return redirect()->route('success');
})->name('submit');


Route::get('/success', function () {
    return view('success');
})->name('success');
