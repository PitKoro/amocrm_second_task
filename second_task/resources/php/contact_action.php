<?php

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

use AmoCRM\Models\CustomFields\EnumModel;
use AmoCRM\Collections\CustomFields\CustomFieldEnumsCollection;

function createPhoneFieldWithValue($customFields, $phone)
{
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
                    ->setValue($phone)
            )
    );

}

function createEmailFieldWithValue($customFields, $email){
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
                    ->setValue($email)
            )
    );
}

function createAgeField($customFieldsContactsService) {
    $customFieldsContactsArray = $customFieldsContactsService->get()->toArray();
    $isAge = false;

    foreach ($customFieldsContactsArray as $field) {
        if ($field['code'] == 'AGE') $isAge = true;
    }

    if (!$isAge) {
        $result = $customFieldsContactsService->get(); //Получим коллекцию значений полей контактов

        $ageField = (new NumericCustomFieldModel())
            ->setCode('AGE')
            ->setName('Возраст');

        $customFieldsContactsService->addOne($ageField);
    }
}

function addValueToAgeField($customFields, $age){
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
                    ->setValue($age)
            )
    );
}


function createGenderField($customFieldsContactsService)
{
    
    $customFieldsContactsArray = $customFieldsContactsService->get()->toArray();
    $isGender = false;

    foreach ($customFieldsContactsArray as $field) {
        if ($field['code'] == 'GENDER') $isGender = true;
    }


    if (!$isGender) {

        $result = $customFieldsContactsService->get(); //Получим коллекцию значений полей контактов

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

    return;
}

function addValueToGenderField($customFields, $gender){

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
                    ->setValue($gender)
            )
    );
}


?>