<?php
namespace App\MyClasses;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\RadiobuttonCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\RadiobuttonCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\RadiobuttonCustomFieldValueModel;
use AmoCRM\Models\CustomFields\EnumModel;
use AmoCRM\Collections\CustomFields\CustomFieldEnumsCollection;

use AmoCRM\Models\CustomFields\RadiobuttonCustomFieldModel;
use AmoCRM\Models\CustomFields\NumericCustomFieldModel;


class Contact extends ContactModel
{
    private AmoCRMApiClient $apiClient;

    function __construct($apiClient, $fields)
    {
        $this->apiClient = $apiClient;
        $this->setName($fields['name'] . ' ' . $fields['surname'])
            ->setPhone($fields['phone'])
            ->setEmail($fields['email'])
            ->setGender($fields['gender'])
            ->setAge($fields['age']);
    }

    public function setPhone($phone)
    {
        $existingCustomFieldsValues = $this->getCustomFieldsValues();

        $phoneField = (new MultitextCustomFieldValuesModel())
            ->setFieldCode('PHONE')
            ->setValues(
                (new MultitextCustomFieldValueCollection())
                    ->add(
                        (new MultitextCustomFieldValueModel())
                            ->setEnum('WORK')
                            ->setValue($phone)
                    )
            );

        if (!is_null($existingCustomFieldsValues)) {
            $existingCustomFieldsValues->add($phoneField);
        } else {
            $customFieldsValues = new CustomFieldsValuesCollection();
            $customFieldsValues->add($phoneField);
            $this->setCustomFieldsValues($customFieldsValues);
        }

        return $this;
    }

    public function setEmail($email)
    {

        $existingCustomFieldsValues = $this->getCustomFieldsValues();

        $emailField = (new MultitextCustomFieldValuesModel())
            ->setFieldCode('EMAIL')
            ->setValues(
                (new MultitextCustomFieldValueCollection())
                    ->add(
                        (new MultitextCustomFieldValueModel())
                            ->setEnum('WORK')
                            ->setValue($email)
                    )
            );

        if (!is_null($existingCustomFieldsValues)) {
            $existingCustomFieldsValues->add($emailField);
        } else {
            $customFieldsValues = new CustomFieldsValuesCollection();
            $customFieldsValues->add($emailField);
            $this->setCustomFieldsValues($customFieldsValues);
        }

        return $this;
    }

    public function setAge($age)
    {
        $customFieldsContactsService = $this->apiClient->customFields(EntityTypesInterface::CONTACTS);
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

        $existingCustomFieldsValues = $this->getCustomFieldsValues();

        //Установим значение поля
        $ageField = (new NumericCustomFieldValuesModel())
            ->setFieldCode('AGE')
            ->setValues(
                (new NumericCustomFieldValueCollection())
                    ->add(
                        (new NumericCustomFieldValueModel())
                            ->setValue($age)
                    )
            );

        if (!is_null($existingCustomFieldsValues)) {
            $existingCustomFieldsValues->add($ageField);
        } else {
            $customFieldsValues = new CustomFieldsValuesCollection();
            $customFieldsValues->add($ageField);
            $this->setCustomFieldsValues($customFieldsValues);
        }

        return $this;
    }

    public function setGender($gender)
    {
        $customFieldsContactsService = $this->apiClient->customFields(EntityTypesInterface::CONTACTS);
        $customFieldsContactsArray = $customFieldsContactsService->get()->toArray();
        $isGender = false;

        foreach ($customFieldsContactsArray as $field) {
            if ($field['code'] == 'GENDER') $isGender = true;
        }


        if (!$isGender) {
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


        $existingCustomFieldsValues = $this->getCustomFieldsValues();

        $genderField = (new RadiobuttonCustomFieldValuesModel())
            ->setFieldCode('GENDER')
            ->setValues(
                (new RadiobuttonCustomFieldValueCollection())
                    ->add(
                        (new RadiobuttonCustomFieldValueModel())
                            ->setValue($gender)
                    )
            );

        if (!is_null($existingCustomFieldsValues)) {
            $existingCustomFieldsValues->add($genderField);
        } else {
            $customFieldsValues = new CustomFieldsValuesCollection();
            $customFieldsValues->add($genderField);
            $this->setCustomFieldsValues($customFieldsValues);
        }

        return $this;
    }
}
