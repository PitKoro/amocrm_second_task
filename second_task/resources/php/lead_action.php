<?php

use AmoCRM\Models\LeadModel;

function createLeadWithRandomResponsibleUser($apiClient, $firstName, $lastName){
    $lead = (new LeadModel())->setName("Сделка от {$firstName} {$lastName}"); // создаем сделку

    //Получим всех пользователей аккаунта
    $users = $apiClient->getRequest()->get('/api/v4/users'); // получаем всех пользователей
    $usersArray = $users['_embedded']['users'];
    $randomKey = array_rand($usersArray); // выбираем случайного пользователя

    $lead->setResponsibleUserId((int)$usersArray[$randomKey]['id']); // меняем ответственного в сделке

    return $lead;    
}

?>