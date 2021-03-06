<?php

namespace ProcessMaker\Http\Controllers\Api\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use ProcessMaker\Http\Controllers\Controller;
use Illuminate\Pagination\Paginator;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\Application;

/**
 * API endpoint for returning Cases
 */
class CasesController extends Controller
{


    /**
     * This function return information by searching cases
     *
     * The query is related to advanced search with diferents filters
     * We can search by process, status of case, category of process, users, delegate date from and to
     *
     * @param string $userUid
     * @param integer $start for the pagination
     * @param integer $limit for the pagination
     * @param string $request ->search
     * @param integer $process the pro_UID
     * @param integer $status of the case
     * @param string $dir if the order is DESC or ASC
     * @param string $sort name of column by sort
     * @param string $category uid for the process
     * @param date $dateFrom
     * @param date $dateTo
     * @param string $request ->columnSearch name of column for a specific search
     * @return array $result result of the query
     */

    public function index(Request $request)
    {
        $cases = Delegation::select(
            'APPLICATION.uid',
            'APPLICATION.APP_STATUS',
            'APPLICATION.APP_STATUS AS APP_STATUS_LABEL',
            'processes.uid AS PRO_UID',
            'APPLICATION.APP_CREATE_DATE',
            'APPLICATION.APP_FINISH_DATE',
            'APPLICATION.APP_UPDATE_DATE',
            'APPLICATION.APP_TITLE',
            'users.uid AS USR_UID',
            'tasks.uid as TAS_UID',
            'delegations.index',
            'delegations.last_index',
            'delegations.delegate_date',
            'delegations.init_date',
            'delegations.finish_date',
            'delegations.task_due_date',
            'delegations.risk_date',
            'delegations.thread_status',
            'delegations.priority',
            'delegations.duration',
            'delegations.queue_duration',
            'delegations.started',
            'delegations.delay_duration',
            'delegations.finished',
            'delegations.delayed',
            'delegations.delay_duration',
            'tasks.title AS APP_TAS_TITLE',
            'tasks.type AS APP_TAS_TYPE',
            'users.lastname',
            'users.firstname',
            'users.username',
            'processes.name AS APP_PRO_TITLE'
        )
        ->join('APPLICATION', 'delegations.application_id', '=', 'APPLICATION.id')
        ->join('tasks', 'delegations.task_id', '=', 'tasks.id')
        ->join('users', 'delegations.user_id', '=', 'users.id')
        ->join('processes', 'APPLICATION.process_id', '=', 'processes.id')
        ->whereNotIn('tasks.type', [
            "WEBENTRYEVENT",
            "END-MESSAGE-EVENT",
            "START-MESSAGE-EVENT",
            "INTERMEDIATE-THROW-MESSAGE-EVENT",
            "INTERMEDIATE-CATCH-MESSAGE-EVENT"
            ]);

        switch ($request->status) {
            case 1:
                $cases
                    ->where('delegations.thread_status', 'OPEN')
                    ->where('APPLICATION.APP_STATUS_ID', Application::STATUS_DRAFT);
                break;
            case 2:
                $cases
                    ->where('delegations.thread_status', 'OPEN')
                    ->where('APPLICATION.APP_STATUS_ID', Application::STATUS_TO_DO);
                break;
            case 3:
                $cases
                    ->where('delegations.last_index', '1')
                    ->where('APPLICATION.APP_STATUS_ID', Application::STATUS_COMPLETED);
                break;
            case 4:
                $cases
                    ->where('delegations.last_index', '1')
                    ->where('APPLICATION.APP_STATUS_ID', Application::STATUS_CANCELLED);
                break;
            default:
                $cases
                    ->where('delegations.thread_status', 'OPEN')
                    ->orWhere('delegations.thread_status', 'CLOSED')
                    ->where('delegations.last_index', '1')
                    ->where('APPLICATION.APP_STATUS_ID', Application::STATUS_COMPLETED);
                break;

        }

        if ($request->has('userUid') && $request->userUid <> '') {
            $cases->where('delegations.user_id', $request->userUid);
        }

        if ($request->has('process') && $request->process <> '') {
            $cases->where('delegations.process_id', $request->process);
        }

        if ($request->has('category') && $request->category <> '') {
            $cases->where('PROCESS.PRO_CATEGORY', $request->category);
        }

        if ($request->has('search') && $request->search <> '') {
            if ($request->has('columnSearch') && in_array($request->columnSearch, ['APP_TITLE', 'APP_NUMBER'])) {
                $application = Application::where($request->columnSearch, 'LIKE', "%{$request->search}%");

                if ($request->columnSearch == 'APP_NUMBER') {
                    if (substr($request->search, 0, 1) != '0' && ctype_digit($request->search)) {
                        $application->where($request->columnSearch, '>=', $request->search);
                    }
                }

                if ($application->count() > 0) {
                    $cases->whereIn('delegations.id', $application->pluck('APP_NUMBER', 'APP_NUMBER'));
                }
            } elseif ($request->has('columnSearch') && $request->columnSearch === 'TAS_TITLE') {
                $cases->where('tasks.title', 'LIKE', "%{$request->search}%");
            }
        }

        if ($request->has('dateFrom') && $request->dateFrom <> '') {
            $cases->where('delegations.delegate_date', '>=', Carbon\Carbon::createFromFormat('Y-m-d', $request->dateFrom));
        }

        if ($request->has('dateTo') && $request->dateTo <> '') {
            $cases->where('delegations.delegate_date', '>=', Carbon\Carbon::createFromFormat('Y-m-d 23:59:59', $request->dateTo));
        }

        if ($request->has('sort')) {
            $sort = 'delegations.id';

            if ($request->sort == 'APP_CURRENT_USER') {
                $sort = 'USR_LASTNAME, USR_FIRSTNAME';
            }

            $dir = "asc";

            if ($request->dir == 'desc') {
                $dir = "desc";
            }

            $cases->orderBy($sort, $dir);
        }

        $limit = 25;

        if ($request->has('limit') && $request->limit > 0) {
            $limit = (int)$request->limit;
        }

        return $cases->paginate($limit);

    }
}
