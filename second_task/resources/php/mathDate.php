<?php

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
        $days = daysBeforeTask($days + 1);
    }
    return (int)$days;
}