<?php
namespace App\Dtos;

/**
 * UserTaskCalendar
 */
class UserTaskCalendarDto {

    public $userId;
    public $dayNumber;
    public $taskPeriodOrder;
    public $taskType;

    function __construct(){
    }

}
