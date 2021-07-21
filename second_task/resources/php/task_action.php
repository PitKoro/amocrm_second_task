<?php

use AmoCRM\Models\TaskModel;
use AmoCRM\Collections\TasksCollection;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Exceptions\AmoCRMApiException;

function createTaskToLead($apiClient, $lead, $daysToTask){
    //Создадим задачу
    $tasksCollection = new TasksCollection();
    $task = new TaskModel();
    $task->setTaskTypeId(TaskModel::TASK_TYPE_ID_CALL)
        ->setText('Новая задача')
        ->setCompleteTill(mktime(6, 0, 0, 7, ((int)date('d') + $daysToTask), 2021))
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
}