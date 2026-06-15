<?php

namespace App\Http\Controllers\Modules\ContentPages;

use App\Helpers\Helper;
use App\Http\Controllers\DatatableTrait;
use App\Http\Controllers\ModuleController;
use App\Models\ContentPage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use PhpOffice\PhpWord\Reader\ODText\Content;


class ContentPagesController extends ModuleController
{
    use DatatableTrait;

    public function __construct()
    {
        parent::__construct();
        $this->setModuleName('contentPages');
    }

    public function index():View
    {
        $this->injectDatatable();

        //$content = ContentPage::all()->sortByDESC('id')->toArray();
        //Project::all()->sortByDesc("name");
        return $this->view('index');
    }

    public function add():View
    {
        $type = ["Article", "Online Course", "Webinar"];
        return $this->view('add',['types'=>$type]);
    }

    public function edit($id):View
    {
        $page = ContentPage::where("id", "=", $id)->first();
        return $this->view('edit', ['data'=>$page]);
    }

    public function create(Request $request)
    {
        Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required',
        ])->validate();
        $page = new ContentPage();
        $page->title = $request->input('title');
        $page->description = $request->input("description");
        $page->save();
        if (!empty($request->input('saveClose'))) {
            return redirect()->route($this->mRoute('home'))->with('success', 'Content Page Created Successfully!');
        } else {
            return redirect()->route($this->mRoute('add'))->with('success', 'Content Page Created Successfully!');

        }

    }

    public function update(Request $request)
    {
        Validator::make($request->all(), [
            'id' => 'required|numeric|min:1',
            'title' => 'required|string',
            'description' => 'required',
        ])->validate();
        $cdata = $request->except('_token', '_method');

        //ContentPage::where("id", "=", $request->input("id"))->update(["slug"=>null]);


        $page = ContentPage::where("id", "=", $request->input("id"))->first();
        $page->slug = null;
        $page->title = $request->input("title");
        $page->description = $request->input("description");
        $page->update();

        return redirect()->route($this->mRoute('home'))->with('success', 'Content Page Updated Successfully!');
    }

    public function delete($id):JsonResponse
    {
        ContentPage::where("id", "=", $id)->delete();
        return response()->json(["status"=>"SUCCESS"]);
    }


    protected function getDataTableRows(): array
    {
        return ContentPage::all()->sortByDESC('id')->toArray();
    }

    protected function getDataTableColumns(): array
    {
        return [
            ["data" => "id"],
            ["data" => "title"],
            ["data" => "actionStatus", "orderable" => false, "searchable" => false, "onAction" => function ($row) {
                //delete_row('.$row["id"].','.route('module.categories.delete',[$row["id"]]).')
                $statusFun = "change_status(" . $row["id"] . ",'" . route($this->mRoute('status'), [$row["id"],'status']) . "','" . csrf_token() . "',this)";
                $checkStatus = "" . ($row['status'] == 1 ? 'checked' : '') . "";
                $btn = '<input switch-button onchange="' . $statusFun . '" ' . $checkStatus . ' type="checkbox" >';
                return $btn;
            }],
            ["data" => "action", "orderable" => false, "searchable" => false, "onAction" => function ($row) {
                //delete_row('.$row["id"].','.route('module.suppliers.delete',[$row["id"]]).')
                $deleteFun = "delete_row(" . $row["id"] . ",'" . route($this->mRoute('delete'), [$row["id"]]) . "','" . csrf_token() . "',this)";
                $btn = '&nbsp;&nbsp;<a href=' . route($this->mRoute('edit'), [$row['id']]) . '><i class="fas fa-edit"></i></a><a hidden href="javascript:" onclick="' . $deleteFun . '" style="color: red!important;"><i class="fas fa-trash"></i></a>';
                return $btn;
            }],
        ];
    }

    protected function getModuleTable() : string
    {
        return (new User())->getTable();
    }
}
