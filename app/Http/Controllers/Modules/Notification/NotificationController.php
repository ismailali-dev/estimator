<?php

namespace App\Http\Controllers\Modules\Notification;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DatatableTrait;
use App\Http\Controllers\ModuleController;
use Illuminate\Http\Request;
use App\Models\HomeCards;
use App\Models\User;

class NotificationController extends ModuleController
{
    use DatatableTrait;

    public function __construct()
    {
        parent::__construct();
        $this->setModuleName('notification');
    }

    public function index(){
        $notifications = auth()->user()->unreadNotifications;
        $notifications = 
        dd($notifications);
        return "hello world";
    }

    protected function getDataTableColumns(): array
    {
        return [
            ["data" => "id"],
            ["data" => "title"],
            ["data" => "link"],
            ["data" => "type"],
            ["data" => "status", "orderable" => false, "searchable" => false, "onAction" => function ($row) {
                //delete_row('.$row["id"].','.route('module.suppliers.delete',[$row["id"]]).')
                $statusFun = "change_status(" . $row["id"] . ",'" . route($this->mRoute('status'), [$row["id"],'status']) . "','" . csrf_token() . "',this)";
                $checkStatus = "" . ($row['status'] == 1 ? 'checked' : '') . "";
                $btn = '<input switch-button onchange="' . $statusFun . '" ' . $checkStatus . ' type="checkbox" >';
                return $btn;
            }],
            ["data" => "action", "orderable" => false, "searchable" => false, "onAction" => function ($row) {
                //delete_row('.$row["id"].','.route('module.suppliers.delete',[$row["id"]]).')
                $deleteFun = "delete_row(" . $row["id"] . ",'" . route($this->mRoute('delete'), [$row["id"]]) . "','" . csrf_token() . "',this)";
                $btn = '<a href=' . route($this->mRoute('edit'), [$row['id']]) . '><i class="fas fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:" onclick="' . $deleteFun . '" style="color: red!important;"><i class="fas fa-trash"></i></a>';
                return $btn;
            }],
        ];
    }

    protected function getModuleTable() : string
    {
        return (new HomeCards())->getTable();
    }

    protected function getDataTableRows(): array
    {
        return HomeCards::orderBy('id', 'DESC')->get()->toArray();
        //return HomeCards::where('is_archive', 0)->where("type", "!=", "APP-USER")->orderBy('id', 'DESC')->get()->toArray();
    }
}
