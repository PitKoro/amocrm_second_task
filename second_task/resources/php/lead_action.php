<?php

use AmoCRM\Models\LeadModel;
use AmoCRM\Filters\CatalogsFilter;
use AmoCRM\Collections\LinksCollection;

function createLeadWithRandomResponsibleUser($apiClient, $firstName, $lastName){
    $lead = (new LeadModel())->setName("Сделка от {$firstName} {$lastName}"); // создаем сделку

    //Получим всех пользователей аккаунта
    $users = $apiClient->getRequest()->get('/api/v4/users'); // получаем всех пользователей
    $usersArray = $users['_embedded']['users'];
    $randomKey = array_rand($usersArray); // выбираем случайного пользователя

    $lead->setResponsibleUserId((int)$usersArray[$randomKey]['id']); // меняем ответственного в сделке

    return $lead;    
}

function addProductsToLead($apiClient, $lead){
    // Получаем список товаров
    $productsCatalog = $apiClient->catalogs()->get(
        (new CatalogsFilter())->setType('products')
    );
    $products = $apiClient->catalogElements($productsCatalog->first()->getId())->get();


    // Привязываем товары к сделке
    $links = new LinksCollection();
    foreach ($products as $product) {
        $links->add($product);
    }
    $apiClient->leads()->link($lead, $links);
}

?>