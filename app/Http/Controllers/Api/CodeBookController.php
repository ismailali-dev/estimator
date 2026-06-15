<?php

namespace App\Http\Controllers\Api;

use App\Mail\CodeBookMail;
use App\Models\Membership;
use App\Models\User;
use App\Services\DownloadEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use ZipStream\File;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Style\Table as TableStyle;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
class CodeBookController extends ApiBaseController
{
    //
    public function index()
    {

    }
/*
    function createEstimateDocFile(){
        try{
            $user = auth()->user();
            $company = $user->company;

            //\PhpOffice\PhpWord\Settings::setZipClass(Settings::PCLZIP);
            \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

            $templatePath = storage_path('downloadable-formats/codebook-doc-format.docx');

            $templateProcessor = new TemplateProcessor($templatePath);

            $templateProcessor->setValue('company_name', $company->name);
            $templateProcessor->setValue("date", date('F j, Y'));
            $templateProcessor->setValue("company_address", $company->address);
            $templateProcessor->setValue("company_phone",$user->phone);
            $templateProcessor->setValue("company_email", $user->email);


            $table = new Table(array('width' => 10000, 'unit' => TblWidth::TWIP));

            $codes = $company->codes()->with([
                "supplier",
                "productGroup"
            ])->get();
               // Add row
               $tableStyle = array('borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80);
               $firstRowStyle = array('bgColor' => '66BBFF');
               $cellStyle = array('valign' => 'center');
               $fontStyle = array('bold' => true);
                $table->addRow();
                $table->addCell(500, $cellStyle)->addText('S/N', $fontStyle);
                $table->addCell(2000, $cellStyle)->addText('Code Name', $fontStyle);
                $table->addCell(2000, $cellStyle)->addText('Product Name', $fontStyle);
                $table->addCell(1000, $cellStyle)->addText('Quantity', $fontStyle);
                $table->addCell(1000, $cellStyle)->addText('SKU No.', $fontStyle);
                $table->addCell(1000, $cellStyle)->addText('Model No.', $fontStyle);
                $table->addCell(1000, $cellStyle)->addText('Unit of Measure', $fontStyle);
                $table->addCell(1000, $cellStyle)->addText('Labor Cost', $fontStyle);
                $table->addCell(1000, $cellStyle)->addText('Material Cost', $fontStyle);
                $table->addCell(1000, $cellStyle)->addText('Misc Cost', $fontStyle);
            for ($i = 0;$i < count($codes);$i++) {
                $table->addRow();
                $sno = $i;
                $table->addCell(10)->addText(++$sno);
                $table->addCell(150)->addText(optional($codes[$i])->code_name);
                $table->addCell(150)->addText(optional($codes[$i])->product_name);
                $table->addCell(50)->addText(optional($codes[$i])->quantity . " " .optional($codes[$i])->unit);
                $table->addCell(50)->addText(optional($codes[$i])->sku_no);
                $table->addCell(50)->addText(optional($codes[$i])->model_no);
                $table->addCell(50)->addText(optional($codes[$i])->unit_of_measure);
                $table->addCell(50)->addText(optional($codes[$i])->labor_cost);
                $table->addCell(50)->addText(optional($codes[$i])->material_cost);
                $table->addCell(50)->addText(optional($codes[$i])->misc_cost);

            }
            $templateProcessor->setComplexBlock('table', $table);
            $fileName = 'codes'.time().'.docx';

            $filePath = storage_path('codes/' . $fileName);
            $templateProcessor->saveAs($filePath);

            return [
                "filename" => $fileName,
                "filepath" => $filePath,
            ];
        }catch(\Exception $exp){
            return $exp->getMessage();
        }

    }*/

/*    public function download()
    {
        try{
            $user = auth()->user();
            $company = $user->company;


            $codes = $company->codes()->with([
                "supplier",
                "productGroup"
            ])->get();
            if(count($codes) == 0){
                $this->response_data["data"] = "No codes to download";
                return $this->sendJsonResponse();
            }
            $resp = $this->createEstimateDocFile();
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="'.$resp['filename'].'"',
            ];
            // Return the file as a download response
            return response()->download($resp['filepath'],$resp['filename'],$headers);//->deleteFileAfterSend(true);
        }catch(\Exception $exp){
            $this->response_data["status"] = false;
            $this->response_data["data"] = $exp->getMessage();
            $this->response_data["message"] = "something went wrong";
            return $this->sendJsonResponse();
        }

    }*/

    public function search(){
        $keyword = request()->get("q", "");
        $users = User::where("company_id", "=", auth()->user()->company_id)
        ->where("is_admin",0)
        ->where(function ($query) use ($keyword) {
            $query->where("first_name", 'like', '%' . $keyword . '%')
                  ->orWhere("last_name", 'like', '%' . $keyword . '%')
                  ->orWhere("email", 'like', '%' . $keyword . '%');
        });

        $users = $users->get(["id", "email","first_name","last_name"]);


        $this->response_data["status"] = true;
        $this->response_data["data"] = ["users"=>$users];
        return $this->sendJsonResponse();
    }

    public function email(Request $request){
        try{

            if(!(auth()->user()->hasSubscription(Membership::SUBSCRIPTION_CODE_BOOK_SUPPORT)) ) {
                $this->response_data["message"] = "Please upgrade your membership.";
                $this->response_data["no_subscription"] = true;
                return $this->sendJsonResponse();
            }

            $validator = Validator::make($request->all(), [
                'id' => ['required', 'array'],
                'id.*' => ['distinct', function ($attribute, $value, $fail) {
                    if (!User::where("id",$value)->where("company_id",auth()->user()->company_id)->first()) {
                        $fail('The selected ' . $value . ' ID is invalid.');
                    }
                }],
            ]);

            if ($validator->fails()){
                foreach ($validator->errors()->all() as $msg) {
                    if (strlen(trim($msg)) > 1){
                        $this->response_data["message"] = $msg;
                        return $this->sendJsonResponse();
                    }
                }
            }

            $downloadEmailService = new DownloadEmailService();
            $resp = $downloadEmailService->createCodeDocFile(auth()->user());

            User::whereIn("id",$request->id)->get()->map(function($user) use($resp){
                Mail::to($user->email)
                ->send(new CodeBookMail($user,$resp['filepath']));
            });


            $this->response_data["status"] = true;
            $this->response_data["message"] = "Email sent successfully";


            return $this->sendJsonResponse();
        }catch(\Exception $exp){
            $this->response_data["status"] = false;
            $this->response_data["data"] = $exp->getMessage();
            $this->response_data["message"] = "something went wrong";
            return $this->sendJsonResponse();
        }



    }

    public function subscription(){
        $this->response_data["status"] = true;
        if(!(auth()->user()->hasSubscription(Membership::SUBSCRIPTION_CODE_BOOK_SUPPORT)) ) {
            $this->response_data["message"] = "Please upgrade your membership.";
            $this->response_data["no_subscription"] = true;
            return $this->sendJsonResponse();
        }
        $this->response_data["message"] = "Codebook Subscription exists";
        $this->response_data["no_subscription"] = false;
        return $this->sendJsonResponse();
    }


}
