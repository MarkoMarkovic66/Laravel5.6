<?php
namespace App\Dtos;

/**
 * 会員情報を管理するクラス
 */
class UserDto {

    public $userId;

    public $arugoUserId;
    public $srUserId;

    public $cwRoomId;
    public $cwRoomName;
    public $cwAccountId;

    public $firstName;
    public $lastName;
    public $firstNameEn;
    public $lastNameEn;

    public $mail;
    public $phoneNumber;
    public $curriculumId;
    public $studySec;
    public $status;

    public $matrix;

    public $openedAt;
    public $closedAt;
    public $resignedAt;

    public $accountType;
    public $activated;
    public $quitted;

    public $isDeleted;
    public $createdAt;
    public $updatedAt;
    public $deletedAt;

    public $lessonPeriodSince;
    public $lessonPeriodUntil;

    function __construct(){
    }
}
