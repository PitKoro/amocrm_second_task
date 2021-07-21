<?php
namespace php\MyClasses;

use AmoCRM\Client\AmoCRMApiClient;

use AmoCRM\Models\LeadModel;
use AmoCRM\Filters\CatalogsFilter;
use AmoCRM\Collections\LinksCollection;

use AmoCRM\Models\TaskModel;
use AmoCRM\Collections\TasksCollection;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Exceptions\AmoCRMApiException;
// use php\Classes\Helper;

include_once '../resources/php/MyClasses/Helper.php';



class Lead extends LeadModel{
    private AmoCRMApiClient $apiClient;

    function __construct($apiClient, $name )
    {
        $this->apiClient = $apiClient;
        $this->setName("Сделка от {$name}")
             ->setRandomResponsibleUser(); // создаем сделку
    }

    public function setRandomResponsibleUser(){
        $users = $this->apiClient->getRequest()->get('/api/v4/users'); // получаем всех пользователей
        $usersArray = $users['_embedded']['users'];
        $randomKey = array_rand($usersArray); // выбираем случайного пользователя

        $this->setResponsibleUserId((int)$usersArray[$randomKey]['id']); // меняем ответственного в сделке

        return $this;    
    }

    public function addProductsToLead()
    {
        // Получаем список товаров
        $productsCatalog = $this->apiClient->catalogs()->get(
            (new CatalogsFilter())->setType('products')
        );
        $products = $this->apiClient->catalogElements($productsCatalog->first()->getId())->get();


        // Привязываем товары к сделке
        $links = new LinksCollection();
        foreach ($products as $product) {
            $links->add($product);
        }
        $this->apiClient->leads()->link($this, $links);
    }

    public function createTaskToLead()
    {
        $helper = new Helper();
        $daysToTask = $helper->daysBeforeTask();
        //Создадим задачу
        $tasksCollection = new TasksCollection();
        $task = new TaskModel();
        $task->setTaskTypeId(TaskModel::TASK_TYPE_ID_CALL)
            ->setText('Новая задача')
            ->setCompleteTill(mktime(6, 0, 0, 7, ((int)date('d') + $daysToTask), 2021))
            ->setEntityType(EntityTypesInterface::LEADS)
            ->setEntityId((int)$this->getId())
            ->setDuration(9 * 60 * 60)
            ->setResponsibleUserId((int)$this->getResponsibleUserId());
        $tasksCollection->add($task);
        try {
            $tasksCollection = $this->apiClient->tasks()->add($tasksCollection);
        } catch (AmoCRMApiException $e) {

        }

        return $this;
    }

}











// function createLeadWithRandomResponsibleUser($apiClient, $firstName, $lastName){
//     $lead = (new LeadModel())->setName("Сделка от {$firstName} {$lastName}"); // создаем сделку

//     //Получим всех пользователей аккаунта
//     $users = $apiClient->getRequest()->get('/api/v4/users'); // получаем всех пользователей
//     $usersArray = $users['_embedded']['users'];
//     $randomKey = array_rand($usersArray); // выбираем случайного пользователя

//     $lead->setResponsibleUserId((int)$usersArray[$randomKey]['id']); // меняем ответственного в сделке

//     return $lead;    
// }

// function addProductsToLead($apiClient, $lead){
//     // Получаем список товаров
//     $productsCatalog = $apiClient->catalogs()->get(
//         (new CatalogsFilter())->setType('products')
//     );
//     $products = $apiClient->catalogElements($productsCatalog->first()->getId())->get();


//     // Привязываем товары к сделке
//     $links = new LinksCollection();
//     foreach ($products as $product) {
//         $links->add($product);
//     }
//     $apiClient->leads()->link($lead, $links);
// }

?>