<?php

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use AmoCRM\Client\AmoCRMApiClient;

use AmoCRM\Exceptions\AmoCRMApiException;


use League\OAuth2\Client\Token\AccessTokenInterface;

use AmoCRM\Collections\LinksCollection;

use AmoCRM\Models\AccountModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFields\EnumModel;
use AmoCRM\Collections\CustomFields\CustomFieldsCollection;
use AmoCRM\Collections\CustomFields\CustomFieldEnumsCollection;

use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;

use AmoCRM\Models\CustomFields\NumericCustomFieldModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel;

use AmoCRM\Models\CustomFields\RadiobuttonCustomFieldModel;
use AmoCRM\Models\CustomFieldsValues\RadiobuttonCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\RadiobuttonCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\RadiobuttonCustomFieldValueModel;

use AmoCRM\Collections\UsersCollection;
use AmoCRM\Models\Rights\RightModel;
use AmoCRM\Models\UserModel;


use AmoCRM\Filters\CatalogsFilter;


use AmoCRM\Helpers\EntityTypesInterface;









use Symfony\Component\HttpFoundation\Session\Session;


include_once '../vendor/autoload.php';
include_once '../vendor/amocrm/amocrm-api-library/examples/error_printer.php';
include_once '../resources/php/token_actions.php';
include_once '../resources/php/lead_action.php';


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

    $contactFields = $request->request->all();
    $session = new Session();
    $session->start();

    // создаем контакт с всеми полями
    $contact = new ContactModel();
    // Добавляем к контакту имя и фамилию

    $contact->setFirstName($contactFields['name'])->setLastName($contactFields['surname']);

    // Добавляем номер телефона к контакту

    //Получим коллекцию значений полей контакта
    $customFields = $contact->getCustomFieldsValues();

    //Получим значение поля по его коду
    $phoneField = $customFields->getBy('fieldCode', 'PHONE');

    //Если значения нет, то создадим новый объект поля и добавим его в коллекцию значений
    if (empty($phoneField)) {
        $phoneField = (new MultitextCustomFieldValuesModel())->setFieldCode('PHONE');
        $customFields->add($phoneField);
    }

    //Установим значение поля
    $phoneField->setValues(
        (new MultitextCustomFieldValueCollection())
            ->add(
                (new MultitextCustomFieldValueModel())
                    ->setEnum('WORK')
                    ->setValue($contactFields['phone'])
            )
    );

    // Добавляем email

    // //Получим коллекцию значений полей контакта

    //Получим значение поля по его коду
    $emailField = $customFields->getBy('fieldCode', 'EMAIL');

    //Если значения нет, то создадим новый объект поля и добавим его в коллекцию значений
    if (empty($emailField)) {
        $emailField = (new MultitextCustomFieldValuesModel())->setFieldCode('EMAIL');
        $customFields->add($emailField);
    }

    //Установим значение поля
    $emailField->setValues(
        (new MultitextCustomFieldValueCollection())
            ->add(
                (new MultitextCustomFieldValueModel())
                    ->setEnum('WORK')
                    ->setValue($contactFields['email'])
            )
    );


    // добавление кастомного поля age
    // //Получим значение поля по его коду
    // $ageField = $customFields->getBy('fieldID', 976729);

    // //Если значения нет, то создадим новый объект поля и добавим его в коллекцию значений
    // if (empty($ageField)) {
    //     $ageField = (new NumericCustomFieldValuesModel())->setFieldId(976729);
    //     $customFields->add($ageField);
    // }

    // //Установим значение поля
    // $ageField->setValues(
    //     (new NumericCustomFieldValueCollection())
    //         ->add(
    //             (new NumericCustomFieldValueModel())
    //                 ->setValue($contactFields['age'])
    //         )
    // );


    //Получим значение поля по его коду
    // $genderField = $customFields->getBy('fieldID', 1024001);

    // //Если значения нет, то создадим новый объект поля и добавим его в коллекцию значений
    // if (empty($genderField)) {
    //     $genderField = (new RadiobuttonCustomFieldValuesModel())->setFieldId(1024001);
    //     $customFields->add($genderField);
    // }

    // //Установим значение поля
    // $genderField->setValues(
    //     (new RadiobuttonCustomFieldValueCollection())
    //         ->add(
    //             (new RadiobuttonCustomFieldValueModel())
    //                 ->setValue($contactFields['gender'])
    //         )
    // );



    //------------- Динамическое добавление поля gender-------------------------------------------------------

    //Получим коллекцию значений полей контактов
    $customFieldsContactsService = $apiClient->customFields(EntityTypesInterface::CONTACTS);
    $customFieldsContactsArray = $customFieldsContactsService->get()->toArray();
    $isGender = false;

    foreach($customFieldsContactsArray as $field){
        if($field['code'] == 'GENDER') $isGender = true;
    }


    if(!$isGender){
        try {
            $result = $customFieldsContactsService->get(); //Получим коллекцию значений полей контактов
            // dd($result);
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        $fieldData = [
            "10" => "мужской",
            "20" => "женский"
        ];
        $enumModel = new CustomFieldEnumsCollection();
        foreach ($fieldData as $sort => $enum) {
            $enumModel->add((new EnumModel())->setValue($enum)->setSort($sort));
        }

        $genderField = (new RadiobuttonCustomFieldModel())
            ->setCode('GENDER')
            ->setName('Пол')
            ->setEnums($enumModel);

        $customFieldsContactsService->addOne($genderField);
    }


    //Получим значение поля по его коду
    $genderField = $customFields->getBy('fieldCode', 'GENDER');

    //Если значения нет, то создадим новый объект поля и добавим его в коллекцию значений
    if (empty($genderField)) {
        $genderField = (new RadiobuttonCustomFieldValuesModel())->setFieldCode('GENDER');
        $customFields->add($genderField);
    }

    //Установим значение поля
    $genderField->setValues(
        (new RadiobuttonCustomFieldValueCollection())
            ->add(
                (new RadiobuttonCustomFieldValueModel())
                    ->setValue($contactFields['gender'])
            )
    );
    //===============================================================================================


    //---------------------Динамическое добавление поля age------------------------------------

    $isAge = false;

    foreach ($customFieldsContactsArray as $field) {
        if ($field['code'] == 'AGE') $isAge = true;
    }

    if (!$isAge) {
        try {
            $result = $customFieldsContactsService->get(); //Получим коллекцию значений полей контактов
            // dd($result);
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        $ageField = (new NumericCustomFieldModel())
            ->setCode('AGE')
            ->setName('Возраст');

        $customFieldsContactsService->addOne($ageField);
    }

    //Получим значение поля по его коду
    $ageField = $customFields->getBy('fieldCode', 'AGE');

    //Если значения нет, то создадим новый объект поля и добавим его в коллекцию значений
    if (empty($ageField)) {
        $ageField = (new NumericCustomFieldValuesModel())->setFieldCode('AGE');
        $customFields->add($ageField);
    }

    //Установим значение поля
    $ageField->setValues(
        (new NumericCustomFieldValueCollection())
            ->add(
                (new NumericCustomFieldValueModel())
                    ->setValue($contactFields['age'])
            )
    );
    //=========================================================================================


    
    // dd($customFieldsContactsService->get());
    // dd($contact->getCustomFieldsValues());
    




    // добавляем контакт
    $apiClient->contacts()->addOne($contact);


// ----------------- Создаем сделку и меняем ответственного на случайного--------------------------------------
    $lead = createLeadWithRandomResponsibleUser($apiClient, $contactFields['name'], $contactFields['surname']);

// ===========================================================================================


    // Добавляем сделку
    $apiClient->leads()->addOne($lead);

    // Вешаем сделку к контакту
    $apiClient->contacts()->link($contact, (new LinksCollection())->add($lead));

    
// ------------------------Добавляем товары --------------------------------------------------

    // Получаем список товаров
    $productsCatalog = $apiClient->catalogs()->get(
        (new CatalogsFilter())->setType('products')
    );
    $products = $apiClient->catalogElements($productsCatalog->first()->getId())->get();
    // dd($products);

    // Привязываем товары к сделке
    $links = new LinksCollection();

    foreach ($products as $product) {
        $links->add($product);
    }

    $apiClient->leads()->link($lead, $links);

    // ===========================================================================================



    return redirect()->route('success');
})->name('submit');


Route::get('/success', function () {
    return view('success');
})->name('success');
