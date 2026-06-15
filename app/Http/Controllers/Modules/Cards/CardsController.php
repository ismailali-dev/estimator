<?php

namespace App\Http\Controllers\Modules\Cards;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DatatableTrait;
use App\Http\Controllers\ModuleController;
use App\Models\HomeCards;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CardsController extends ModuleController
{
    use DatatableTrait;

    public $url_regex = '/((http|https)\:\/\/)?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.([a-zA-Z0-9\&\.\/\?\:@\-_=#])*/';

    public function __construct()
    {
        parent::__construct();
        $this->setModuleName('cards');
    }
    public function index()
    {
        $this->injectDatatable();
        return $this->view('index');
    }

    public function add():View
    {
        $type = ["Article", "Online Course", "Webinar"];
        return $this->view('add',['types'=>$type]);
    }

    public function edit($id):View
    {
        $card = HomeCards::where("id", "=", $id)->first();
        $type = ["Article", "Online Course", "Webinar"];
        return $this->view('edit', ['types'=>$type, 'data'=>$card]);
    }

    public function create(Request $request)
    {
        Validator::make($request->all(), [
            'title' => 'required|string',
            'link' => ['required', 'regex:'.$this->url_regex],
            'type' => 'required|in:'.implode(",",["Article", "Online Course", "Webinar"]),

        ])->validate();


        $homeCard = new HomeCards();
        $homeCard->title = $request->input('title');
        $homeCard->link = $request->input("link");
        $homeCard->type = $request->input("type");
        $homeCard->short_detail = $request->input('short_detail');
        $homeCard->save();
        if (!empty($request->input('saveClose'))) {
            return redirect()->route($this->mRoute('home'))->with('success', 'Card Created Successfully!');
        } else {
            return redirect()->route($this->mRoute('add'))->with('success', 'Card Created Successfully!');

        }

    }

    public function update(Request $request)
    {
        Validator::make($request->all(), [
            'title' => 'required|string',
            'link' => ['required', 'regex:'.$this->url_regex],
            'type' => 'required|in:'.implode(",",["Article", "Online Course", "Webinar"]),
        ])->validate();
        $cdata = $request->except('_token', '_method');

        $card = HomeCards::where("id", "=", $request->input("id"))->update($cdata);

        return redirect()->route($this->mRoute('home'))->with('success', 'Card Updated Successfully!');
    }

    public function delete($id):JsonResponse
    {
        HomeCards::where("id", "=", $id)->delete();
        return response()->json(["status"=>"SUCCESS"]);
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
