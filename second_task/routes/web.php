<?php

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Collections\Customers\CustomersCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Models\ContactModel;
use AmoCRM\Helpers\EntityTypesInterface;

use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Filters\CustomersFilter;

use AmoCRM\Models\Customers\CustomerModel;
use AmoCRM\Exceptions\AmoCRMApiException;

use AmoCRM\Models\TaskModel;
use AmoCRM\Collections\TasksCollection;

use Symfony\Component\HttpFoundation\Session\Session;

include_once '../vendor/autoload.php';
include_once '../vendor/amocrm/amocrm-api-library/examples/error_printer.php';
include_once '../resources/php/token_actions.php';
include_once '../resources/php/lead_action.php';
include_once '../resources/php/contact_action.php';

function isWeekend($date)
{
    $isWeekend = false;
    $numberOfTheWeek = date('N', $date);
    if (($numberOfTheWeek >= 6) && ($numberOfTheWeek <= 7)) {
        $isWeekend = true;
    }
    return $isWeekend;
}

function daysBeforeTask($days = 4)
{
    $date = strtotime("+$days day");
    if (isWeekend($date)) {
        // dd('ы попали в условие');
        daysBeforeTask($days + 1);
    }
    // dd((int)$days);
    return (int)$days;
}


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


    // $daysToTask = daysBeforeTask();
    // dd($daysToTask);



    //Получим всех пользователей аккаунта
    // $users = $apiClient->getRequest()->get('/api/v4/leads'); // получаем всех пользователей
    // dd($users);

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

        $leadWithWonStatus = $leadsWithFilter->first()->setStatusId(142); // меняем statusId  на 142
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
            dd(1);
            printError($e);
            die;
        }

        return redirect()->route('success');
    }

    $contact = new ContactModel(); // создаем контакт

    $contact->setFirstName($contactFields['name'])->setLastName($contactFields['surname']); // Добавляем к контакту имя и фамилию

    $customFields = $contact->getCustomFieldsValues(); //Получим коллекцию значений полей контакта

    createPhoneFieldWithValue($customFields, $contactFields['phone']); // создаем поле для телефона и записываем туда значение
    createEmailFieldWithValue($customFields, $contactFields['email']); // Создаем поле для email и записываем туда значение 

    $customFieldsContactsService = $apiClient->customFields(EntityTypesInterface::CONTACTS);

    createGenderField($customFieldsContactsService); // создаем поле gender
    addValueToGenderField($customFields, $contactFields['gender']); // записываем значение в поле gender

    createAgeField($customFieldsContactsService); // создаем поле age
    addValueToAgeField($customFields, $contactFields['age']); // записываем значение в поле age

    $apiClient->contacts()->addOne($contact); // добавляем контакт

    $lead = createLeadWithRandomResponsibleUser($apiClient, $contactFields['name'], $contactFields['surname']); //Создаем сделку и меняем ответственного на случайного

    $apiClient->leads()->addOne($lead); // Добавляем сделку

    $apiClient->contacts()->link($contact, (new LinksCollection())->add($lead)); // Привязываем сделку к контакту

    addProductsToLead($apiClient, $lead); // добавляем товары к сделке

    $num = 4;

    $targetwday = date('N', strtotime("+{$num} days"));
    $extradays = $targetwday > 5 ? 8 - $targetwday : 0;
    $days = $num + $extradays;
    $targetdate = strtotime("+{$days} days");
    $taskDay = date('d', $targetdate);


    //Создадим задачу
    $tasksCollection = new TasksCollection();
    $task = new TaskModel();
    $task->setTaskTypeId(TaskModel::TASK_TYPE_ID_CALL)
        ->setText('Новая задача')
        ->setCompleteTill(mktime(6, 0, 0, 7, (int)$taskDay, 2021))
        ->setEntityType(EntityTypesInterface::LEADS)
        ->setEntityId((int)$lead->getId())
        ->setDuration(9 * 60 * 60)
        ->setResponsibleUserId((int)$lead->getResponsibleUserId());
    $tasksCollection->add($task);

    try {
        $tasksCollection = $apiClient->tasks()->add($tasksCollection);
    } catch (AmoCRMApiException $e) {
        printError($e);
        die;
    }

    return redirect()->route('success');
})->name('submit');


Route::get('/success', function () {
    return view('success');
})->name('success');
