<?php

namespace App\Http\Controllers\Test;

#use App\Mail\PasswordForget;
use App\Models\Account;
use App\Models\AccountKind;
use App\Models\BatchLog;
use App\Models\CwFeedbackLog;
use App\Models\UserMessageLog;
use App\Models\Grammar;
use App\Models\GslVerbMatrix;
use App\Models\GslWordFreq;
use App\Models\OtherTask;
use App\Models\Review;
use App\Models\ReviewPeriod;
use App\Models\SpeakingRally;
use App\Models\SrCategory;
use App\Models\SrTopic;
use App\Models\Task;
use App\Models\TaskSetting;
use App\Models\User;
use App\Models\UserTask;
use App\Models\UserFeedback;
use App\Models\UserLessonLog;
use App\Models\UserScore;
use App\Models\UserTaskPeriod;
use App\Models\Vocabulary;
use App\Models\UserOutput;
use App\Models\UserListening;
use App\Models\UserLearningPolicy;
use App\Models\UserGoal;
use App\Models\LpCategory;
use Agent;

use App\Mail\Contact;
use App\Mail\Signin;

#use Aws\Common\Facade\S3;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\BaseViewController;
//use App\Models\User;
use Hash;
use DB;

use Illuminate\Support\Facades\Auth;
//use App\Facades\S3ServiceFacade;
use \App\Services\S3Service;

use AlueDataImportService;



class AppController extends BaseViewController
{

	//テスト
    public function index(Request $request)
	{

        $Account = new Account();
        $data = $Account->getAll();
        var_dump($data);

        $AccountKind = new AccountKind();
        $data = $AccountKind->getAll();
        var_dump($data);

        $BatchLog = new BatchLog();
        $data = $BatchLog->getAll();
        var_dump($data);

        $CwFeedbackLog = new CwFeedbackLog();
        $data = $CwFeedbackLog->getAll();
        var_dump($data);

        $CwLog = new UserMessageLog();
        $data = $CwLog->getAll();
        var_dump($data);

        $Grammar = new Grammar();
        $data = $Grammar->getAll();
        var_dump($data);

        $GslVerbMatrix = new GslVerbMatrix();
        $data = $GslVerbMatrix->getAll();
        var_dump($data);

        $GslWordFreq = new GslWordFreq();
        $data = $GslWordFreq->getAll();
        var_dump($data);

        $OtherTask = new OtherTask();
        $data = $OtherTask->getAll();
        var_dump($data);

        $Review = new Review();
        $data = $Review->getAll();
        var_dump($data);

        $ReviewPeriod = new ReviewPeriod();
        $data = $ReviewPeriod->getAll();
        var_dump($data);

        $SpeakingRally = new SpeakingRally();
        $data = $SpeakingRally->getAll();
        var_dump($data);

        $SrCategory = new SrCategory();
        $data = $SrCategory->getAll();
        var_dump($data);

        $SrTopic = new SrTopic();
        $data = $SrTopic->getAll();
        var_dump($data);

        $Task = new Task();
        $data = $Task->getAll();
        var_dump($data);

        $TaskSetting = new TaskSetting();
        $data = $TaskSetting->getAll();
        var_dump($data);

        $User = new User();
        $data = $User->getAll();
        var_dump($data);

        $UserTask = new UserTask();
        $data = $UserTask->getAll();
        var_dump($data);

        $UserFeedback = new UserFeedback();
        $data = $UserFeedback->getAll();
        var_dump($data);

        $UserLessonLog = new UserLessonLog();
        $data = $UserLessonLog->getAll();
        var_dump($data);

        $UserScore = new UserScore();
        $data = $UserScore->getAll();
        var_dump($data);

        $UserTaskPeriod = new UserTaskPeriod();
        $data = $UserTaskPeriod->getAll();
        var_dump($data);

        $Vocabulary = new Vocabulary();
        $data = $Vocabulary->getAll();
        var_dump($data);

        $UserOutput = new UserOutput();
        $data = $UserOutput->getAll();
        var_dump($data);

        $UserListening = new UserListening();
        $data = $UserListening->getAll();
        var_dump($data);

        $UserLearningPolicy = new UserLearningPolicy();
        $data = $UserLearningPolicy->getAll();
        var_dump($data);

        $UserGoal = new UserGoal();
        $data = $UserGoal->getAll();
        var_dump($data);

        $LpCategory = new LpCategory();
        $data = $LpCategory->getAll();
        var_dump($data);

        return view('Test.index', $this->view_params);

	}


    public function batchTest() {

        AlueDataImportService::exec();

    }


}
