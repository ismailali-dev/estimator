<?php

namespace App\Http\Controllers\Modules\Contractor;

use App\Helpers\Helper;
use App\Http\Controllers\DatatableTrait;
use App\Http\Controllers\ModuleController;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Code;
use App\Models\Template;
use App\Models\Estimate;
use App\Models\EstimateSheet;
use App\Models\UserInvoiceSetting;
use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractorController extends ModuleController
{
    use DatatableTrait;

    public function __construct($offset = 13)
    {
        parent::__construct();
        $this->setModuleName('contractor');
    }






    public function index()
    {
        $this->injectDatatable();
        return $this->view('index');
    }

    public function edit($id){
        $user = User::where("id", "=", $id)->where("type", "=", 3)->with('JobTypes')->first();
        return $this->view('view', ['user'=>$user]);
    }

    public function update(Request $request)
    {
        Validator::make($request->all(), [
            'status' => 'required|numeric',
        ])->validate();
        $cdata = $request->except('_token', '_method');
        $status = $request->input("status");
        $contractor = User::where("id", "=", $request->input("id"))->first();

        if(!empty($contractor)){
            $card = User::where("id", "=", $request->input("id"))->update(["status"=>$status]);
            if ($contractor->status != $status && !empty($contractor->device_token)){
                $title = (($status) ? "Congratulations" : "Information");
                $message = (($status) ? "".$contractor->full_name." your account has been approved." : "".$contractor->full_name." your account has been disabled.");
                $recipients = [$contractor->device_token];
                $jsonResponse = Helper::sendFCM($recipients, ["title"=>$title, "body"=>$message])->getData();
                if ($jsonResponse->status){
                    return redirect()->route($this->mRoute('home'))->with('success', $contractor->full_name.' his account status '.(($status) ? 'enabled' : 'disabled').' and notification send successfully!');
                }else{
                    return redirect()->route($this->mRoute('home'))->with('error', $contractor->full_name.' his account status '.(($status) ? 'enabled' : 'disabled').' and notification sending failed!');
                }
            }
            return redirect()->route($this->mRoute('home'));
        }
        return redirect()->route($this->mRoute('home'))->with('error', 'Contractor not found!');







    }

    public function suppliers($userId){
        $user = User::where("type", "=", 3)->where("id", "=", $userId)->first();
        if (empty($user)){
            return redirect()->route($this->mRoute('home'));
        }
        $suppliers = Supplier::where("user_id", "=", $userId)->get();

        $data = [];
        foreach ($suppliers as $s){
            $data[] = [
                "id" => $s->id,
                "full_name" => $s->full_name,
                "phone" => $s->phone,
                "email" => $s->email,
                "address" => $s->address,
                "state" => $s->supplier_state,
                "city" => $s->supplier_city,
                "zip_code" => $s->zip_code
            ];
        }
        return $this->view("suppliers", ["data"=>$data, 'contractor'=>$user]);
    }

    public function customers($userId){
        $user = User::where("type", "=", 3)->where("id", "=", $userId)->first();
        if (empty($user)){
            return redirect()->route($this->mRoute('home'));
        }
        $contractorCustomers = Customer::where("user_id", "=", $userId)->get();

        $customers = [];
        foreach ($contractorCustomers as $c){
            $customers[] = [
                "id" => $c->id,
                "full_name" => $c->full_name,
                "phone" => $c->phone,
                "email" => $c->email,
                "address" => $c->address,
                "state" => $c->customer_state,
                "city" => $c->customer_city,
                "zip_code" => $c->zip_code
            ];
        }
        return $this->view("customers", ["customers"=>$customers, 'contractor'=>$user]);
    }

    public function estimates($userId){

        $user = User::where("type", "=", 3)->where("id", "=", $userId)->first();
        if (empty($user)){
            return redirect()->route($this->mRoute('home'));
        }
        $contractorEstimates = Estimate::where("user_id", "=", $userId)->get();

        $data = [];
        foreach ($contractorEstimates as $e){
            $data[] = [
                "id" => $e->id,
                "scope" => $e->estimate_scope,
                "date" => date("m/d/Y", strtotime($e->estimate_date_time)),
                "customer_name" => $e->customer->full_name,
                "report" => route($this->mRoute("estimate.report"), ["userId"=>$userId, "estimateId"=>$e->id]),
                "edit" => route($this->mRoute("estimate.edit"), ["userId"=>$userId, "estimateId"=>$e->id]),
                "delete" => "delete_dt_row(".$e->id.", '".route($this->mRoute("estimate.delete"), ["userId"=>$userId, "estimateId"=>$e->id])."', '".csrf_token()."', this)",
            ];
        }
        return $this->view("estimates", ["estimates"=>$data, 'contractor'=>$user]);
    }

    public function estimate_report($userId, $estimateId){

        $estimate = Estimate::with(["customer", "Sheet"])->where("user_id", "=", $userId)->where("id", "=", $estimateId)->first();
        if(empty($estimate)){
            return redirect()->route($this->mRoute('home'));
        }
        $user = User::find($userId);
        $stateCity = City::find($estimate->customer->city);
        $customer = [
            "full_name" => $estimate->customer->full_name,
            "phone" => $estimate->customer->phone,
            "email" => $estimate->customer->email,
            "address" => $estimate->customer->address,
            "zip_code" => $estimate->customer->zip_code,
            "state_name" => $stateCity->state->state_name,
            "city_name" => $stateCity->city_name,
        ];
        $sheetGroups = [];
        $tmpProductGroups = EstimateSheet::select("product_group_id")->with('ProductGroup')->where("estimate_id", "=", $estimate->id)->groupBy("product_group_id")->get()->sortBy('ProductGroup.group_name');
        if (!empty($tmpProductGroups)){
            foreach ($tmpProductGroups as $pg){
                $sheetGroups[] = [
                    "id"=>$pg->ProductGroup->id,
                    "group_name"=>$pg->ProductGroup->group_name,
                    "group_items"=>EstimateSheet::with('Code')->where("estimate_id", "=", $estimate->id)->where("product_group_id", "=", $pg->ProductGroup->id)->get()->sortBy("Code.sort_order")
                ];
            }
        }
        $invoiceSetting = UserInvoiceSetting::where("user_id", "=", $userId)->first();
        $invoice_logo = "";
        $invoice_color = "#f1f1f1";
        if (!empty($invoiceSetting)){
            if ($user->payment_type){
                $path = str_ireplace("storage/app/", "", $invoiceSetting->logo);
                if (Storage::exists($path)){
                    $file = Storage::get($path);
                    $ext = pathinfo($path)["extension"];
                    $base64 = base64_encode($file);
                    $invoice_logo = "data:image/".$ext.";base64,".$base64;
                }
            }
            $invoice_color = $invoiceSetting->color;
        }
        $htmlData = [
            'logo' => $invoice_logo,
            'color' => $invoice_color,
            'report' => [
                "date" => date("d-M-y"),
                "page" => "",
                "company_name" => $user->company_name,
                "company_address" => $user->company_address,
                "report_by" => $user->full_name,
                "phone" => $user->phone,
                "customer" => $customer,
                "invoice" =>$estimate,
                "invoice_detail" => $sheetGroups,
            ]
        ];
        $reportName = 'estimate-sheet-'.$estimate->user_id."-".str_replace("/", "-", $estimate->estimate_date).'.pdf';
        $pdf = PDF::loadView('reports.estimate', $htmlData);
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();

        $pageWidth = $canvas->get_width();
        $pageHeight = $canvas->get_height();
        $canvas->page_text($pageWidth - 85, 48, " {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0, 0, 0));

        /*$content = $pdf->download()->getOriginalContent();
        if (Storage::exists('reports/'.$reportName)){
            Storage::delete("reports/".$reportName);
        }
        Storage::put("reports/".$reportName,$content);*/

        return $pdf->download($reportName);


    }

    public function estimate_edit($userId, $estimateId){
        $user = User::where("type", "=", 3)->where("id", "=", $userId)->first();
        if (empty($user)){
            return redirect()->route($this->mRoute('home'));
        }
        $sheet = EstimateSheet::where("user_id", "=", $userId)->where("estimate_id", "=", $estimateId)->with('Code')->get();
        foreach ($sheet as $s){

        }
        $estimate = Estimate::where("user_id", "=", $userId)->where("id", "=", $estimateId)->first();
        return $this->view("estimate-edit", ["contractor"=> $user, "estimate"=>$estimate, "sheet"=>$sheet]);
    }

    public function estimate_update(Request $request, $userId, $estimateId)
    {
        Validator::make($request->all(), [
            'estimate_scope' => 'required|string',
            'estimate_date' => 'required|date_format:m/d/Y',
            'sheet_id.*' => 'required|numeric|min:1',
            'quantity.*' => 'required|numeric',
            'labor_cost.*' => 'required|numeric',
            'material_cost.*' => 'required|numeric',

        ])->validate();
        $cdata = $request->except('_token', '_method');
        $postData = $request->input();
        $sheets = [];
        foreach($postData["sheet_id"] as $k=>$sheetId){
            $sheets[] = [
                "id" => $sheetId,
                "quantity" => $postData["quantity"][$k],
                "labor_cost" => $postData["labor_cost"][$k],
                "material_cost" => $postData["material_cost"][$k],
            ];
        }
        foreach ($sheets as $item) {
            if (intval($item["quantity"]) > 0) {
                EstimateSheet::where("user_id", "=", $userId)->where("estimate_id", "=", $estimateId)->where("id", "=", $item["id"])
                    ->update(["quantity" => $item["quantity"], "labor_cost" => $item["labor_cost"], "material_cost" => $item["material_cost"]]);
            } else {
                EstimateSheet::where("user_id", "=", $userId)->where("estimate_id", "=", $estimateId)->where("id", "=", $item["id"])->delete();
            }
        }
        Estimate::where("user_id", "=", $userId)->where("id", "=", $estimateId)->update(["estimate_scope"=>$postData["estimate_scope"]]);

        //return redirect()->route($this->mRoute('home'))->with('success', 'Card Updated Successfully!');

        return redirect()->route($this->mRoute('estimates'), ["userId"=>$userId])->with('success', 'Estimate Updated Successfully!');
    }

    public function estimate_code_update(Request $request, $userId, $codeId):JsonResponse
    {
        $json = [
            "status" => "SUCCESS",
            "redirect" => "",
        ];

        Code::where("user_id", "=", $userId)->where("id", "=", $codeId)->update(["description"=>$request->input("descp")]);

        return response()->json($json);
    }

    public function estimate_delete(Request $request, $userId, $estimateId):JsonResponse
    {

        EstimateSheet::where("user_id", "=", $userId)->where("estimate_id", "=", $estimateId)->delete();
        Estimate::where("user_id", "=", $userId)->where("id", "=", $estimateId)->delete();
        return response()->json(["status"=>"SUCCESS"]);
    }

    public function estimate_sheet_item_delete(Request $request, $userId, $estimateId):JsonResponse
    {
        $json = [
            "status" => "SUCCESS",
            "redirect" => "",
        ];

        $action = $request->input("action");
        if ($action == "1"){
            EstimateSheet::where("user_id", "=", $userId)->where("estimate_id", "=", $estimateId)->where("id", "=", $request->input("id"))->delete();
            Estimate::where("user_id", "=", $userId)->where("id", "=", $estimateId)->delete();
            $json["redirect"] = route($this->mRoute('estimates'), ["userId"=>$userId]);
        }else{
            $estimateSheet = EstimateSheet::where("user_id", "=", $userId)->where("estimate_id", "=", $estimateId)->where("id", "!=", $request->input("id"))->get();
            if (!empty($estimateSheet)){
                if ($estimateSheet->count() > 0){
                    $json["message"] = "count ".$estimateSheet->count();
                    EstimateSheet::where("user_id", "=", $userId)->where("estimate_id", "=", $estimateId)->where("id", "=", $request->input("id"))->delete();
                }else{
                    $json["status"] = "CONFIRMATION";
                }
            }
        }
        return response()->json($json);
    }

    protected function getDataTableRows(): array
    {
        $users = [];
        $contractors = User::where('is_archive', 0)->where('type', "=", 3)->with('JobTypes')->orderBy('id', 'DESC')->get();
        foreach ($contractors as $key=>$val){
            $users[] = [
                "id" => $val->id,
                "name"=>$val->full_name,
                "phone"=>$val->phone,
                "company_name"=>@$val->company->name,
                "email"=>$val->email,
                "job_type" => ($val->JobTypes()->exists() ? $val->JobTypes->title : ''),
                "license_no" => $val->license_no,
                "suppliers"=>Supplier::where("user_id", "=", $val->id)->count(),
                "customers"=>Customer::where("user_id", "=", $val->id)->count(),
                "estimates"=>Estimate::where("user_id", "=", $val->id)->count(),
                "templates"=>Template::where("user_id", "=", $val->id)->count(),
                "codes"=>Code::where("user_id", "=", $val->id)->count(),
            ];
        }
        return $users;
        //return User::where('is_archive', 0)->where('type', "=", 3)->orderBy('id', 'DESC')->get()->toArray();
    }

    protected function getDataTableColumns(): array
    {
        return [
            ["data" => "id"],
            ["data" => "name"],
            ["data" => "phone"],
            ["data" => "company_name"],
            ["data" => "email"],
            ["data" => "suppliers", "onAction" => function($row){
                return '<a href="'.route("module.contractor.suppliers", ['userId'=>$row["id"]]).'">'.$row["suppliers"].'</a>';
            }],
            ["data" => "customers", "onAction" => function($row){
                return '<a href="'.route("module.contractor.customers", ['userId'=>$row["id"]]).'">'.$row["customers"].'</a>';
            }],
            ["data" => "estimates", "onAction" => function($row){
                return '<a href="'.route("module.contractor.estimates", ['userId'=>$row["id"]]).'">'.$row["estimates"].'</a>';
            }],
            ["data" => "templates"],
            ["data" => "codes"],

            ["data" => "action", "orderable" => false, "searchable"=>false, "onAction" => function($row){
                $invoiceWindow = "window.open('".route($this->mRoute('edit'), [$row['id']])."','popup_name','height=' + screen.height + ',width=' + screen.width + ',directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no')";
                $html = '<a class="dropdown-item" style="cursor: pointer" href="'.route($this->mRoute("edit"), [$row["id"]]).'"><i class="fas fa-info-circle"></i>&nbsp;&nbsp;Detail</a>';
                return $html;
            }],
        ];
    }

    protected function getModuleTable() : string
    {
        return (new User())->getTable();
    }
}
