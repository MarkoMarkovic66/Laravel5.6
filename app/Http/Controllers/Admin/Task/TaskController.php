<?php
namespace App\Http\Controllers\Admin\Task;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseViewController;
use App\Enums\MenuNameType;
use App\Enums\UserTaskStatusType;
use App\Enums\SessionValiableType;

use TaskService;

/**
 * TaskController
 */
class TaskController extends BaseViewController {

    public function index($backToList=false)
	{
        $this->view_params->put('menuName', MenuNameType::MENU_TASK);

        //ログインアカウント情報
        $loginAccountInfo = parent::getLoginUser();

        //検索条件選択肢：ステータス一覧
        $this->view_params->put('searchCondOption_Status', UserTaskStatusType::getStatusList());

        //検索条件選択肢：生徒一覧
        $memberList = TaskService::getMemberList($loginAccountInfo);
        $this->view_params->put('searchCondOption_Member', $memberList->all());

        //検索条件選択肢：オペレータ一覧
        $operatorList = TaskService::getOperatorList($loginAccountInfo);
        $this->view_params->put('searchCondOption_Operator', $operatorList->all());


        if($backToList == 'back'){
            $this->view_params->put('backToList', true);
            //前回検索条件をセッションから取得
            $params = unserialize(session()->get(SessionValiableType::SEARCH_CONDITION_TASK));
            $this->view_params->put('searchCondition', $params);
        }else{
            $this->view_params->put('backToList', false);
            $this->view_params->put('searchCondition', null);
        }

        $this->view_params->put('isOnlyNoAssingnOperatorTask', false);
        
		return view('Admin.Task.index')->with('view_params', $this->view_params);
	}

    public function indexWithNoAssign($backToList=false)
	{
        $this->view_params->put('menuName', MenuNameType::MENU_TASK);

        //ログインアカウント情報
        $loginAccountInfo = parent::getLoginUser();

        //検索条件選択肢：ステータス一覧
        $this->view_params->put('searchCondOption_Status', UserTaskStatusType::getStatusList());

        //検索条件選択肢：生徒一覧
        $memberList = TaskService::getMemberList($loginAccountInfo);
        $this->view_params->put('searchCondOption_Member', $memberList->all());

        //検索条件選択肢：オペレータ一覧
        $operatorList = TaskService::getOperatorList($loginAccountInfo);
        $this->view_params->put('searchCondOption_Operator', $operatorList->all());


        if($backToList == 'back'){
            $this->view_params->put('backToList', true);
            //前回検索条件をセッションから取得
            $params = unserialize(session()->get(SessionValiableType::SEARCH_CONDITION_TASK));
            $this->view_params->put('searchCondition', $params);
        }else{
            $this->view_params->put('backToList', false);
            $this->view_params->put('searchCondition', null);
        }

        $this->view_params->put('isOnlyNoAssingnOperatorTask', true);        
		return view('Admin.Task.index')->with('view_params', $this->view_params);
	}



    /**
     * Chatwork一括投稿画面表示
     * @param Request $request
     */
    public function chatworkContribute(Request $request)
	{
        $this->view_params->put('menuName', MenuNameType::MENU_TASK);

        $userTaskIds = trim(empty($request->get('userTaskIds')) ? '' : $request->get('userTaskIds'));
        $this->view_params->put('userTaskIds', $userTaskIds);

        $taskList = TaskService::getChatworkContributeData($userTaskIds);
        $this->view_params->put('taskList', $taskList);

		return view('Task.chatworkContribute')->with('view_params', $this->view_params);
    }

}
