<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\Membership;
use App\Models\Supplier;
use App\Models\User;
use App\Models\UserInvoiceSetting;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Paragraph;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use App\Models\SettingType;
use App\Models\EstimateSheet;
use App\Models\EstimateSignature;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class DownloadEmailService
{
  /*  public function materialListFile(Estimate $estimate,User $user){
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Material List');
            $sheet->setCellValue('A1', 'Category');
            $sheet->setCellValue('B1', 'Supplier');
            $sheet->setCellValue('C1', 'No. of Codes');
            $sheet->setCellValue('D1', 'Allowance');
            $sheet->setCellValue('E1', 'Total');
            $sheet->setCellValue('F1', 'Difference');

            $estimateService = new EstimateService();
            $data = $estimateService->materialList($estimate);

            $dataValues = $data->values();

            $row = 2;
            // This is the loop to populate data
            for ($i=0; $i < count($dataValues); $i++) {
                $sheet->setCellValue('A' . $row, $dataValues[$i]['category']);
                $sheet->setCellValue('B' . $row, $dataValues[$i]['supplier']);
                $sheet->setCellValue('C' . $row, $dataValues[$i]['codes']);
                $sheet->setCellValue('D' . $row, $dataValues[$i]['allowance']);
                $sheet->setCellValue('E' . $row, $dataValues[$i]['total']);
                $sheet->setCellValue('F' . $row, $dataValues[$i]['difference']);
                $row++;

            }
            $writer = new Xlsx($spreadsheet);
            $fileName = "MaterialList.xlsx";
//                header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
//                header("Content-Disposition: attachment;filename=\"$fileName\"");
            //$writer->save("php://output");

            $filePath = storage_path($fileName);
            $writer->save($filePath);

            return [
                "filename" => $fileName,
                "filepath" => $filePath,
            ];
    }*/

    public function downloadMaterialList(Estimate $estimate,User $user){
        try {
            $company = $user->company;
  //          \PhpOffice\PhpWord\Settings::setZipClass(Settings::PCLZIP);
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            $fontColor = '000000'; // Red color
            $invoiceSetting = UserInvoiceSetting::where("company_id", $company->id)->first();
            if($invoiceSetting && $user->hasSubscription(Membership::SUBSCRIPTION_INVOICE)){
                
                
                $fontColor = $invoiceSetting->color;
                $logoPath = storage_path(str_replace("storage/","",$invoiceSetting->logo)); // Adjust the path as needed
                if (file_exists($logoPath)) {
                    $section->addImage($logoPath, [
                        'width' => 100,
                        'height' => 100,
                        'alignment' => 'center',
                    ]);
                }
                
                 $footer = $section->addFooter();
    
                // Define the footer text style
                $footerStyle = ['size' => 10, 'align' => 'center'];
                $currentDate = date('F j, Y'); // Example format: October 29, 2024
                
                // Add footer content
                
                $footer->addText("This estimate was generated using the EZ-Estimater app on $currentDate.", $footerStyle);
                
            }
            $centeredParagraphStyle = [
                'align' => 'center',
            ];
            $boldFontStyle = [
                'bold' => true,
                'color' => $fontColor,
                'size' => 18,
            ];
            $estimateService = new EstimateService();
            $data = $estimateService->materialListNotGroup($estimate);

            //Add styled text directly
            //Add styled and centered text directly

            $section->addTextBreak(1);
            $section->addText(  "Customer Material List", $boldFontStyle, $centeredParagraphStyle);

            $groupCodes = $data->groupBy("Code.productGroup.group_name");
            //dd($groupCodes->toArray());

            foreach ($groupCodes as $group => $groupData) {
                $table = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 80,'width' => 10000, 'unit' => TblWidth::TWIP]);
                $table->addRow();
                $table->addCell(2000)->addText('Quantity', ['bold' => true, 'color' => $fontColor]);
                $table->addCell(1000)->addText('Unit', ['bold' => true, 'color' => $fontColor]);
                $table->addCell(2000)->addText('Sku', ['bold' => true, 'color' => $fontColor]);
                $table->addCell(2000)->addText('Model', ['bold' => true, 'color' => $fontColor]);
                $table->addCell(5000)->addText($group, ['bold' => true, 'color' => $fontColor,'size' => 14],['align' => 'center']);
                $table->addCell(2000)->addText('Cost', ['bold' => true, 'color' => $fontColor]);
                $table->addCell(1000)->addText('Total Cost', ['bold' => true, 'color' => $fontColor]);
                foreach ($groupData as $key => $value) {
                    $table->addRow();
                    $table->addCell(2000)->addText('', ['color' => $fontColor]);
                    $table->addCell(1000)->addText(optional($value->Code)->unit_of_measure, ['color' => $fontColor]);
                    $table->addCell(2000)->addText(optional($value->Code)->sku_no, ['color' => $fontColor]);
                    $table->addCell(2000)->addText(optional($value->Code)->model_no, ['color' => $fontColor]);
                    $table->addCell(5000)->addText(optional($value->Code)->description, ['color' => $fontColor]);
                    $table->addCell(2000)->addText(optional($value->Code)->material_cost, ['color' => $fontColor]);
                    $table->addCell(2000)->addText('', ['color' => $fontColor]);
                }
                $section->addTextBreak(1);
            }



          /*  $section->addTextBreak(1);
            $section->addTextBreak(2);

            $section->addText(  "Customer Material List", $boldFontStyle, $centeredParagraphStyle);
            $section->addTextBreak(1);
            $section->addTextBreak(2);


            $table = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 80,'width' => 10000, 'unit' => TblWidth::TWIP]);
            $table->addRow();
            $table->addCell(8000)->addText('Description', ['bold' => true, 'color' => $fontColor]);
            $table->addCell(2000)->addText('Quantity', ['bold' => true, 'color' => $fontColor]);
            $table->addCell(2000)->addText('Cost', ['bold' => true, 'color' => $fontColor]);

            foreach ($data as $key => $value) {
                $table->addRow();

                $cost = "$" . optional($value->Code)->material_cost;
                $table->addCell(8000)->addText(optional($value->Code)->description, ['color' => $fontColor]);
                $table->addCell(1000)->addText($value->quantity, ['color' => $fontColor]);
                $table->addCell(2000)->addText($cost, ['color' => $fontColor]);
            }*/


            $fileName = 'customer-material-list' . time() . '.docx';
            $filePath = storage_path('codes/' . $fileName);
            $phpWord->save($filePath, 'Word2007');

            return [
                "filename" => $fileName,
                "filepath" => $filePath,
            ];
        } catch (\Exception $exp) {
            throw new \Exception($exp->getMessage() . " " . $exp->getLine() . " " . $exp->getFile());
        }
    }
    
    
   public function downloadMaterialListData(Estimate $estimate, User $user)
    {
        $estimateService = new EstimateService();
        
        // Retrieve the data
         $data = $estimateService->materialListNotGroup($estimate, false, null, 'customer');
    
        // Sort the data by 'Code.productGroup.group_order' before grouping
        $sortedData = $data->sortBy('Code.productGroup.group_order');
    
        // Now group the sorted data by 'Code.productGroup.group_name'
        $groupCodes = $sortedData->groupBy('Code.productGroup.group_name');
        
        return $groupCodes;
    }
    
    
    
    public function materialListSupplierData(Estimate $estimate)
    {
      
        $estimateService = new EstimateService();
        
        // Retrieve the data
       
        $data = $estimateService->materialListNotGroup($estimate, false, null, 'supplier');
    
        // Sort the data by 'Code.productGroup.group_order' before grouping
        $sortedData = $data->sortBy('Code.productGroup.group_order');
    
        // Now group the sorted data by 'Code.productGroup.group_name'
        $groupCodes = $sortedData->groupBy('Code.productGroup.group_name');
        
        return $groupCodes;
    }

    
    public function materialListSupplier(Estimate $estimate,User $user,Supplier $supplier){
        try {
            $company = $user->company;
           // \PhpOffice\PhpWord\Settings::setZipClass(Settings::PCLZIP);
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            $fontColor = '000000'; // Red color
            $invoiceSetting = UserInvoiceSetting::where("company_id", $company->id)->first();
            
          if ($invoiceSetting && $user->hasSubscription(Membership::SUBSCRIPTION_INVOICE)) {
                // If user has the 'invoice' subscription, no footer text should be shown
                $fontColor = $invoiceSetting->color;
                $logoPath = storage_path(str_replace("storage/", "", $invoiceSetting->logo)); // Adjust the path as needed
                
                if (file_exists($logoPath)) {
                    $section->addImage($logoPath, [
                        'width' => 100,
                        'height' => 100,
                        'alignment' => 'center',
                    ]);
                }
            
                // No footer text will be added if the user has an active invoice subscription
            } else {
                
                // If no invoice setting exists or the user does not have an 'invoice' subscription
                $footer = $section->addFooter();
                
                // Define the footer text style
                $footerStyle = ['size' => 10, 'align' => 'center'];
                $currentDate = date('F j, Y'); // Example format: October 29, 2024
                
                // Add footer content
                $footer->addText("This estimate was generated using the EZ-Estimater app on $currentDate.", $footerStyle);
            }
            $centeredParagraphStyle = [
                'align' => 'center',
            ];
            $boldFontStyle = [
                'bold' => true,
                'color' => $fontColor,
                'size' => 18,
            ];

            //Add styled text directly
            //Add styled and centered text directly
            $section->addTextBreak(1);
            $section->addText(  "Vendor Material List", $boldFontStyle, $centeredParagraphStyle);
            $section->addTextBreak(1);
            $section->addTextBreak(2);

            $estimateService = new EstimateService();
            $data = $estimateService->materialListNotGroup($estimate,true,$supplier);

            $groupCodes = $data->groupBy("Code.productGroup.group_name");


            foreach ($groupCodes as $group => $groupData) {


                $table = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 80, 'width' => 10000, 'unit' => TblWidth::TWIP]);
                $table->addRow();
                $table->addCell(2000)->addText('Quantity', ['bold' => true, 'color' => $fontColor]);
                $table->addCell(1000)->addText('Unit', ['bold' => true, 'color' => $fontColor]);
                $table->addCell(2000)->addText('Sku', ['bold' => true, 'color' => $fontColor]);
                $table->addCell(2000)->addText('Model', ['bold' => true, 'color' => $fontColor]);
                $table->addCell(4000)->addText($group, ['bold' => true, 'color' => $fontColor,'size' => 14],['align' => 'center']);

                foreach ($groupData as $key => $value) {
                    $table->addRow();
                    $table->addCell(2000)->addText('', ['color' => $fontColor]);
                    $table->addCell(1000)->addText(optional($value->Code)->unit_of_measure, ['color' => $fontColor]);
                    $table->addCell(2000)->addText(optional($value->Code)->sku_no, ['color' => $fontColor]);
                    $table->addCell(2000)->addText(optional($value->Code)->model_no, ['color' => $fontColor]);
                    $table->addCell(4000)->addText(optional($value->Code)->code_name, ['color' => $fontColor]);
                }
                $section->addTextBreak(1);
            }

            $fileName = 'supplier-material-list' . time() . '.docx';
            $filePath = storage_path('codes/' . $fileName);
            $phpWord->save($filePath, 'Word2007');

            return [
                "filename" => $fileName,
                "filepath" => $filePath,
            ];
        } catch (\Exception $exp) {
            return $exp->getMessage();
        }
    }
    
    
    function createEstimateDocFileTest(Estimate $estimate,EstimateService $estimateService, User $user){
        $sheets = $estimate->sheet()->with([
            "Code.ProductGroup"
        ])->get();
        $centeredParagraphStyle = ['align' => 'center'];
        $company = $estimate->company;
        $user = $estimate->user;
        $customer = $estimate->customer;
  //      \PhpOffice\PhpWord\Settings::setZipClass(Settings::PCLZIP);
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

        $detail = $estimateService->getDetail($estimate,$user);

        $templatePath = storage_path('downloadable-formats/Estimate-doc-format.docx');

        $templateProcessor = new TemplateProcessor($templatePath);

        $templateProcessor->setValue('company_name', $company->name);
        $templateProcessor->setValue("date", date('F j, Y'));
        $templateProcessor->setValue("company_address", $company->address);
        $templateProcessor->setValue("company_phone",$user->phone);
        $templateProcessor->setValue("company_email", $user->email);
        $templateProcessor->setValue("customer_name", $customer->name);
        $templateProcessor->setValue("customer_address", $customer->address);
        $templateProcessor->setValue("qoute_date", $estimate->created_at->format('M j, Y'));
        $templateProcessor->setValue("scope", $estimate->estimate_scope);
        $templateProcessor->setValue("grand_total", $detail["grand_total"]);

        $table = new Table(array('width' => 10000, 'unit' => TblWidth::TWIP));

        for ($i = 0;$i < count($sheets);$i++) {
            $sno = $i;
            $table->addRow();
            $cell = $table->addCell(null, array('gridSpan' => 3));
            $cell->addText(optional($sheets[$i]->code->ProductGroup)->group_name,array('bold' => true),$centeredParagraphStyle);
            $table->addRow();
            $table->addCell(10)->addText(++$sno);
            $table->addCell(150)->addText(optional($sheets[$i]->code)->description);
            $table->addCell(50)->addText(optional($sheets[$i])->quantity . " " .optional($sheets[$i])->unit);

        }
        $templateProcessor->setComplexBlock('table', $table);
        $fileName = 'estimate'.time().'.docx';

        $filePath = storage_path($fileName);
        $templateProcessor->saveAs($filePath);

        return [
            "filename" => $fileName,
            "filepath" => $filePath,
        ];
    }


    function createEstimateDocFile(Estimate $estimate, EstimateService $estimateService, User $user)
    {
        
        $sheets = $estimate->sheet()
        ->join('codes', 'estimate_sheets.code_id', '=', 'codes.id')
        ->join('product_groups', 'codes.product_group_id', '=', 'product_groups.id')
        ->where('estimate_sheets.quantity', '>', 0) // Exclude records with quantity 0
        ->select('estimate_sheets.*')
        ->orderBy('product_groups.group_order')
        ->get();
        
      
        $company = $estimate->company;
        $customer = $estimate->customer;
        $detail = $estimateService->getDetail($estimate, $user);
        $fontColor = '000000';
        $backgroundColor = 'ffffff';
        $logoPath = public_path('assets/img/logo-old.png'); // Default logo path
        
        $phpWord = new PhpWord();
         
         // Set default font properties for the whole document
        $phpWord->setDefaultFontName('Cambria');
        $phpWord->setDefaultParagraphStyle(
        array(
        //'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
        'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(0),
        'spacing' => 120,
        'lineHeight' => 1,
        )
        );
        
        
        
       
       
        $section = $phpWord->addSection();
        $boldFontStyle = ['bold' => true];
        $normalFontStyle = ['bold' => false];
        $centeredParagraphStyle = ['alignment' => 'center'];
        
        // Define styles
        $logoCellStyle = ['valign' => 'center'];
        $companyInfoStyle = ['size' => 18, 'bold' => true];
        $normalTextStyle = ['size' => 15]; // Normal text style for addresses and contacts
        
        
       $invoiceSetting = UserInvoiceSetting::where("company_id", $company->id)->first();

        if ($invoiceSetting) {
            // If user has an 'invoice' subscription, no footer text should be shown
            if ($user->hasSubscription('invoice')) {
                $fontColor = $invoiceSetting->color; // Update font color if set
                if ($invoiceSetting->logo) {
                    $logoPath = storage_path(str_replace("storage/", "", $invoiceSetting->logo)); 
                }
                $backgroundColor = $fontColor; // Set background color if required
            } else {
                // User does not have the 'invoice' subscription, show footer
                $footer = $section->addFooter();
                
                // Define the footer text style
                $footerStyle = ['size' => 10, 'align' => 'center'];
                $currentDate = date('F j, Y'); // Example format: October 29, 2024
        
                // Add footer content
                $footer->addText("This estimate was generated using the EZ-Estimater app on $currentDate.", $footerStyle);
            }
        } else {
            
            
            
            if (!$user->hasSubscription('invoice')) {
                // If no invoice setting exists, the user is a free user or no invoice settings are configured
                // Show footer for free users
                $footer = $section->addFooter();
            
                // Define the footer text style
                $footerStyle = ['size' => 10, 'align' => 'center'];
                $currentDate = date('F j, Y'); // Example format: October 29, 2024
            
                // Add footer content
                $footer->addText("This estimate was generated using the EZ Estimater app on $currentDate.", $footerStyle);
            }
        }
               
     
        
    
        $estimateSignature = EstimateSignature::where('estimate_id', $estimate->id)
                ->where('user_id', @$user->id) // Filter by current user
                ->first();  

        $signatureData = [
            'estimator_signature' => optional($estimateSignature)->estimator_signature,
            'customer_signature' => optional($estimateSignature)->customer_signature,
            'estimator_signed_at' => optional($estimateSignature)->estimator_signed_at,
            'customer_signed_at' => optional($estimateSignature)->customer_signed_at,
        ];
        
                
    
        
        // Create a table with 3 columns and center alignment in the body (not header)
        $table = $section->addTable(['borderColor' => 'ffffff', 'borderSize' => 0, 'cellMargin' => 50, 'alignment' => 'center']);
        
        // Add a row to the table
        $table->addRow();
        
        // First column: Company Logo
        $logoCell = $table->addCell(1200, $logoCellStyle);
        if (isset($logoPath) && file_exists($logoPath)) {
            $logoCell->addImage($logoPath, ['width' => 60, 'height' => 60]); // Adjust path and size
        }
        
        // Second column: Company Information
        $infoCell = $table->addCell(4500);
        if (isset($company)) {
            $infoCell->addText($company->name, $companyInfoStyle);
            $infoCell->addText(@$company->address, $normalTextStyle);
            $infoCell->addText(@$user->phone, $normalTextStyle);
            $infoCell->addText(@$user->email, $normalTextStyle);
        }
        
        $section->addTextBreak(2);
    
        // Font styles
        $boldFontStyle = ['bold' => true];
        $normalFontStyle = ['bold' => false];
        $centeredParagraphStyle = ['alignment' => 'center'];
    
    
        $section->addText('Estimate', ['bold' => true, 'size' => 18], $centeredParagraphStyle);

        // Adding estimate number and date
        $tableStyle = [
            'borderColor' => 'ffffff',
            'borderSize' => 0,
            'cellMargin' => 80,
            'width' => 10000,
            'unit' => TblWidth::TWIP,
            'alignment' => 'center'
        ];
        $table = $section->addTable($tableStyle);
        $table->addRow();
        
        $cell1 = $table->addCell(5000);
        $textRun1 = $cell1->addTextRun();
        $textRun1->addText('Estimate No: ', ['bold' => true]);
        $textRun1->addText($estimate->key);
        
        $cell2 = $table->addCell(5000, ['alignment' => 'right']);
        $textRun2 = $cell2->addTextRun(['alignment' => 'right']);
        $textRun2->addText('Date: ', ['bold' => true]);
        $textRun2->addText($estimate->created_at->format('M j, Y'));
        
        // Adding Customer and Quote Details
        $table = $section->addTable($tableStyle);
        
        // Header Row
        $table->addRow();
        $table->addCell(5000)->addText('Customer Details:', ['bold' => true, "size" => "13"]);
        $table->addCell(5000)->addText('Quote Details:', ['bold' => true, "size" => "13"], ['alignment' => 'right']);
        
        // Row 1: Name and Estimate Type
        $table->addRow();
        $cell1 = $table->addCell(5000);
        $textRun1 = $cell1->addTextRun();
        $textRun1->addText('Name: ', ['bold' => true]);
        $textRun1->addText(@$customer->name);
        
        $cell2 = $table->addCell(5000, ['alignment' => 'right']);
        $textRun2 = $cell2->addTextRun(['alignment' => 'right']);
        $textRun2->addText('Estimate Type: ', ['bold' => true]);
        $textRun2->addText(@$estimate->estimateType->name);
        
        // Row 2: Phone and Quoted By
        $table->addRow();
        $cell1 = $table->addCell(5000);
        $textRun1 = $cell1->addTextRun();
        $textRun1->addText('Phone: ', ['bold' => true]);
        $textRun1->addText(@$customer->phone);
        
        $cell2 = $table->addCell(5000, ['alignment' => 'right']);
        $textRun2 = $cell2->addTextRun(['alignment' => 'right']);
        $textRun2->addText('Quoted By: ', ['bold' => true]);
        $textRun2->addText(@$user->first_name . ' ' . @$user->last_name);
        
        // Row 3: Address
        $table->addRow();
        $cell1 = $table->addCell(5000);
        $textRun1 = $cell1->addTextRun();
        $textRun1->addText('Address: ', ['bold' => true]);
        $textRun1->addText(@$customer->address);
        
        // Add an empty cell for alignment in the right column
        $table->addCell(5000);
    
        // Adding Scope of Work
        
        $table = $section->addTable($tableStyle);
        
        // Add a row for "Scope of Work"
        $table->addRow();
        $table->addCell(10000)->addText('Scope of Work:', ['bold' => true,"size"=>"13"]); // Full-width cell for title
        
        $table->addRow();
        $table->addCell(10000)->addText($estimate->estimate_scope); // Full-width cell for description
    
        // Adding Specifications
       
        $table = $section->addTable($tableStyle);
        $table->addRow();
        $table->addCell(10000)->addText('Specifications:', ['bold' => true,"size"=>"13"]); // Full
        
    
        
       // Define the table style
        $tableStyle = [
            'borderColor'=>'ffffff',
            'borderSize' => 0, // No border
            'cellMargin' => 80,
            'width' => 10000,
            'unit' => TblWidth::TWIP,
            'alignment' => 'center'
        ];
        
        // Loop through the sorted sheets
        $currentProductGroup = null; // To keep track of the current product group
        $sno = 0; // Serial number starts from 0
        
        
       
        
        foreach ($sheets as $i => $sheet) {
            // Check if the product group has changed
            if ($currentProductGroup !== optional($sheet->code->ProductGroup)->group_name) {
                // Update the current product group
                $currentProductGroup = optional($sheet->code->ProductGroup)->group_name;
        
                // Add a new table for this product group
                $table = $section->addTable($tableStyle); // Use the defined table style
        
                // Add the header row with a full-width title for the product group
                $headerRow = $table->addRow(); // Add the row
        
                // Set the background color for the header row
                $headerCell = $headerRow->addCell(10000, [
                    'gridSpan' => 4,
                    'valign' => 'center',
                    'bgColor' => $backgroundColor // Set the background color here
                ]);
        
                // Add text to the header cell
                $headerCell->addText($currentProductGroup, ['bold' => true], ['align' => 'center']);
        
                // Reset the serial number for the new group
                $sno = 0;
            }
        
            // Increment the serial number
            $sno++; // Start numbering from 1
        
            // Add a new row for the sheet
            $row = $table->addRow();
            $row->addCell(300)->addText((string)$sno . "."); // Serial number
        
            // Add the specification description
            $row->addCell(7700)->addText(optional($sheet->code)->description);
        
            // Centered text for the last two columns
            $cell3 = $row->addCell(1000);
            $cell3->addText(optional($sheet)->quantity, [], ['align' => 'center']); // Quantity centered
        
            $cell4 = $row->addCell(1000);
            $cell4->addText(optional($sheet)->unit, [], ['align' => 'center']); // Unit centered
        }
                
        $section->addTextBreak(3);
        
        $tableStyle = [
            'borderColor' => 'ffffff',
            'borderSize' => 0,
            'cellMargin' => 80,
            'width' => 10000,
            'unit' => TblWidth::TWIP,
            'alignment' => 'center',
        ];
        
        $table = $section->addTable($tableStyle);
        $table->addRow();
        
        // Add the first cell with vertical alignment set to center
        $table->addCell(5000, ['valign' => 'center'])->addText('');
        
        // Add the second cell with text and set vertical alignment to center
        $table->addCell(2000, ['valign' => 'center'])->addText(
            'Grand Total w/tax:',
            ['bold' => true],
            ['align' => 'right']
        );
        
         $detail = [];
         $detail['material'] = $sheets->map(function($sheet){
            return $sheet->total_material_cost != 0 ? round($sheet->total_material_cost, 2) : null;
        })->filter()->values();
        
       $detail = [];
        $sheets = $estimate->sheet;
        
        // Calculate material costs
        $detail['material'] = $sheets->map(function($sheet){
            return $sheet->total_material_cost != 0 ? round($sheet->total_material_cost, 2) : null;
        })->filter()->values();
        
        // Calculate labor costs
        $detail['labor_cost'] = $sheets->map(function($sheet){
            return $sheet->total_labor_cost != 0 ? round($sheet->total_labor_cost, 2) : null;
        })->filter()->values();
        
        // Calculate the subtotals (before tax but with markup)
        $detail['material_sub_total'] = round(collect($detail['material'])->sum(), 2);  // Subtotal for materials
        $detail['labor_sub_total'] = round(collect($detail['labor_cost'])->sum(), 2);   // Subtotal for labor
        
        // Fetch settings for the estimate
        $settingCollection = Setting::where("company_id", $user->company_id)
            ->where("type", SettingType::SETTING_TYPE_FOR_ESTIMATE)
            ->get();
        
        // Step 1: Calculate "material markup" and add to material subtotal
        $settingMaterialMarkup = $settingCollection->where('slug', "material_markup")->first();
        $detail['settings']["material_markup"] = round(($settingMaterialMarkup->value / 100) * $detail['material_sub_total'],2);
        $detail['material_sub_total'] = round($detail['material_sub_total'] + $detail['settings']["material_markup"], 2);
        
        // Step 2: Calculate "labor markup" and add to labor subtotal
        // $settingLaborMarkup = $settingCollection->where('slug', "labor_markup")->first();
        
        
         $settingLaborMarkup = $settingCollection->first(function ($setting) {
                        return in_array($setting->slug, ['labour_markup', 'labor_markup']);
                    });
                    
        
        $detail['settings']["labour_markup"] = (($settingLaborMarkup->value ?? 0) / 100) * $detail['labor_sub_total'];
        $detail['labor_sub_total'] = round($detail['labor_sub_total'] + $detail['settings']["labour_markup"], 2);
        
        // Step 3: Calculate "tax" on material after adding markup
        $settingTax = $settingCollection->where('slug', "tax")->first();
        $detail['settings']["tax"] = (($settingTax->value ?? 0) / 100) * $detail['material_sub_total'];
        $detail['material_total'] = round($detail['material_sub_total'] + $detail['settings']["tax"], 2);
        $detail['settings']["tax"] = round($detail['settings']["tax"],2);
        // Step 4: Calculate the total (material + labor after markup and tax)
        $detail['labor_total'] = $detail['labor_sub_total'];  // labor total remains same as labor_sub_total since no tax on labor
        $detail['total'] = round($detail['material_total'] + $detail['labor_total'], 2);
        
        // Step 5: Initialize grand total
       $detail['grand_total'] = floatval($detail['total']);
        
        // Store the base cost (initial total) to apply taxes on it
        $base_cost = $detail['total'];
        
        // Initialize total other taxes
        $total_other_taxes = 0;
        
        // Calculate other taxes based on the base cost (detail['total'])
        $detail["settings"]["other_tax"] = $settingCollection->whereNotIn('slug', ["material_markup", "labor_markup", "labour_markup", "tax"])
            ->map(function($setting) use (&$detail, $base_cost, &$total_other_taxes) {
                // Calculate each tax based on the base cost
                $setting_tax = round(($setting->value / 100) * $base_cost, 2);
        
                // Add this tax to the total other taxes
                $total_other_taxes += $setting_tax;
        
                // Return the formatted tax amount for display
                return number_format($setting_tax, 2, '.', '');
            })->values();
        
        // Add the total other taxes to the grand total
        $detail['grand_total'] += $total_other_taxes;
            
      
        // Add the third cell with text and set vertical alignment to center
        $table->addCell(2000, ['valign' => 'center'])->addText(
            '$'.number_format((int)$detail['grand_total'], 2),
            ['bold' => true, 'size' => 15],
            ['align' => 'center']
        );
        

        $table = $section->addTable($tableStyle);
       
        // $table->addCell(10000)->addText('This is a quotation based on Customer Specifications and by itself is not a contract to do Work. Pro-Builders Express cannot be responsible for changes in Work or Price due to County, State or Federal Building Requirements that were not Specified nor Based on Approved Plans. Items noted as standard are subject to an additional charge if changed to a custom installation by the owner.:', $normalFontStyle); // Full
    
        
        $this->addSignatureSection($section, $signatureData, $company,$estimate->id);
        
        // $table = $section->addTable($tableStyle);
        // $table->addRow();
        // $table->addCell(2000)->addText('______________',[], ['align' => 'center']); 
        // $table->addCell(2000)->addText('______________',[], ['align' => 'center']); 
        // $table->addCell(2000)->addText('______________',[], ['align' => 'center']); 
        // $table->addCell(2000)->addText('______________',[], ['align' => 'center']); 
        // $table->addRow();
        // $table->addCell(2000)->addText('Customer Signature', $boldFontStyle, $centeredParagraphStyle); 
        // $table->addCell(2000)->addText('Date', $boldFontStyle, $centeredParagraphStyle); 
        // $table->addCell(2000)->addText(@$company->name, $boldFontStyle, $centeredParagraphStyle); 
        // $table->addCell(2000)->addText('Date', $boldFontStyle, $centeredParagraphStyle); 

    

        // Save the document
        $fileName = 'estimate_' . time() . '.docx';
        $filePath = storage_path($fileName);
        $phpWord->save($filePath, 'Word2007');
        
            return [
                "filename" => $fileName,
                "filepath" => $filePath,
            ];
    }



public function addSignatureSection($section, $signatureData, $company = null, $estimateId)
{
  
    $section->addTextBreak(2);

    // Define table style
    $signatureTableStyle = [
        'borderColor' => 'ffffff',
        'borderSize' => 0,
        'cellMargin' => 50,
        'width' => 10000,
        'unit' => TblWidth::TWIP,
        'alignment' => 'center',
    ];
    $signatureTable = $section->addTable($signatureTableStyle);

    // First row for images or placeholders
    $signatureTable->addRow();

    // Customer Signature Cell
    $customerSignatureCell = $signatureTable->addCell(2500, ['alignment' => 'center']);
    if (!empty($signatureData['customer_signature'])) {
        // Convert the base64 string to an image file and retrieve the path
        $customerSignaturePath = $this->processSignatureImage($signatureData['customer_signature'], 'customer_signature.png');
        $customerSignatureCell->addImage($customerSignaturePath, [
            'width' => 60,
            'height' => 60,
            'alignment' => 'center'
        ]);
    } else {
        $customerSignatureCell->addText('', ['italic' => true], ['alignment' => 'center']);
    }

    // Customer Date Cell (aligned bottom)
// Customer Date Cell (aligned bottom)
    $customerDateCell = $signatureTable->addCell(2500, ['alignment' => 'center', 'valign' => 'bottom']);
    $customerDate = !empty($signatureData['customer_signed_at']) 
        ? Carbon::parse($signatureData['customer_signed_at'])->format('m/d/Y') 
        : '';
    $customerDateCell->addText($customerDate, [], ['alignment' => 'center']);

    // Estimator Signature Cell
    $estimatorSignatureCell = $signatureTable->addCell(2500, ['alignment' => 'center']);
    if (!empty($signatureData['estimator_signature'])) {
        // Convert the base64 string to an image file and retrieve the path
        $estimatorSignaturePath = $this->processSignatureImage($signatureData['estimator_signature'], 'estimator_signature' . $estimateId . '.png');
        $estimatorSignatureCell->addImage($estimatorSignaturePath, [
            'width' => 60,
            'height' => 60,
            'alignment' => 'center'
        ]);
    } else {
        $estimatorSignatureCell->addText('', ['italic' => true], ['alignment' => 'center']);
    }

    // Estimator Date Cell (aligned bottom)
   // Estimator Date Cell (aligned bottom)
$estimatorDateCell = $signatureTable->addCell(2500, ['alignment' => 'center', 'valign' => 'bottom']);
$estimatorDate = !empty($signatureData['estimator_signed_at']) 
    ? Carbon::parse($signatureData['estimator_signed_at'])->format('m/d/Y') 
    : '';
$estimatorDateCell->addText($estimatorDate, [], ['alignment' => 'center']);

    // Second row for labels
    $signatureTable->addRow();
    $signatureTable->addCell(2500)->addText('Customer Signature', ['bold' => true], ['alignment' => 'center']);
    $signatureTable->addCell(2500)->addText('Date', ['bold' => true], ['alignment' => 'center']);
    $signatureTable->addCell(2500)->addText(isset($company->name) ? $company->name : 'Estimator Signature', ['bold' => true], ['alignment' => 'center']);
    $signatureTable->addCell(2500)->addText('Date', ['bold' => true], ['alignment' => 'center']);
}


    private function processSignatureImage($base64String, $filename)
    {
        
        
        // Decode the Base64 string and extract the extension
        $image = str_replace('data:image/png;base64,', '', $base64String);
        $image = str_replace(' ', '+', $image);
        $imageName = 'signature_' . time() . '.png'; // Create a unique filename
    
        // Decode the Base64 string
        $imageData = base64_decode($image);
    
        // Define the path to save the image
        
        $path = 'signatures/' . $filename; // Example path, adjust as needed
        Storage::disk('public')->put($path, $imageData);
        return storage_path('app/public/' . $path); // Return the full path 
    
    

    }


    function createCodeDocFile(User $user) {
    try {
        $company = $user->company;
        $fontColor = '000000'; // Default color
        $logoPath = public_path('assets/img/logo-old.png'); // Default logo path
        $invoiceSetting = UserInvoiceSetting::where("company_id", $company->id)->first();

        // Add company logo
        if ($invoiceSetting && $user->hasSubscription(Membership::SUBSCRIPTION_INVOICE)) {
            if ($invoiceSetting->logo) {
                $logoPath = storage_path(str_replace("storage/", "", $invoiceSetting->logo)); 
            }
        }

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        // Set default font properties for the whole document
        $phpWord->setDefaultFontName('Cambria');

       $section = $phpWord->addSection();

        // Create a table to hold the logo and company/user info
        $table = $section->addTable(['borderColor' => 'FFFFFF', 'borderSize' => 0, 'alignment' => 'center']);
        $table->addRow();
        
        // Add the logo
        $logoCell = $table->addCell(1200);
        if (isset($logoPath) && file_exists($logoPath)) {
            $logoCell->addImage($logoPath, ['width' => 60, 'height' => 60]);
        }
        
        // Add company and user information
        $infoCell = $table->addCell(3500);
        $infoCell->addText(@$company->name, ['size' => 20, 'bold' => true, 'color' => $fontColor]);
        $infoCell->addText(@$user->phone);
        $infoCell->addText(@$user->email);
        $infoCell->addText(@$company->address);

        // Initialize the TOC array to store section titles and page numbers
        $toc = [];
        $currentPage = 1; // Start counting pages

        // Add Sales Data
        $tableStyle = [
            'borderColor' => 'ffffff',
            'borderSize' => 0,
            'cellMargin' => 80,
            'width' => 10000,
            'unit' => TblWidth::TWIP,
            'alignment' => 'center',
        ];
        
        $codes = $company->codes()->with(["supplier", "productGroup"])->get();
        $groupCodes = $codes->groupBy("productGroup.group_name");
        
        $section->addTextBreak(2);
        
        foreach ($groupCodes as $productGroup => $groupCode) {
            // Store the starting page number for this group
            $startPage = $currentPage;

            // Add a title for each product group in the TOC
            $table = $section->addTable($tableStyle);
            $table->addRow();
            $table->addCell(1000)->addText('Key Code', ['bold' => true, 'color' => $fontColor]);
            $table->addCell(500)->addText('Cost', ['bold' => true, 'color' => $fontColor]);
            $table->addCell(500)->addText('Units', ['bold' => true, 'color' => $fontColor]);
            $table->addCell(5000)->addText($productGroup, ['bold' => true, 'color' => $fontColor, 'size' => 14], ['align' => 'center']);
           
            foreach ($groupCode as $i => $code) {
                $table->addRow();
                $cost = "$" . (optional($code)->labor_cost + optional($code)->material_cost);

                // Define the text style with the specified color, underline, and bold for the code name only
                $codeTextStyle = [
                    'color' => '3481b9', // Your specified color in RGB hex format
                    'underline' => 'single', // Underline style
                    'bold' => true // Set bold
                ];

                // Define a default style for the other cells (optional)
                $defaultTextStyle = [
                    'color' => '000000', // Default color
                    'bold' => false // Not bold
                ];

                // Apply the style only to the key code
                $table->addCell(1000)->addText($code->code_name, $codeTextStyle);
                $table->addCell(500)->addText($cost, $defaultTextStyle);
                $table->addCell(500)->addText(optional($code)->unit_of_measure, $defaultTextStyle);
                $table->addCell(5000)->addText(optional($code)->description . " " . optional($code)->unit, $defaultTextStyle);
            }

            // Store the ending page number for this group
            $toc[] = [
                'title' => $productGroup,
                'startPage' => $startPage,
                'endPage' => $currentPage + count($groupCode) // Assuming each code takes one page
            ];

            $currentPage += count($groupCode); // Update the current page count
        }

        

        // Add footer with two cells: one for text and one for page number
        $footer = $section->addFooter();
        $footerTable = $footer->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 50]); // Adjust cellMargin for spacing
        $footerTable->addRow();

        $footerTextCell = $footerTable->addCell(8000, ['cellMargin' => 100]); // Added cellMargin for spacing
        $currentDateTime = date('Y-m-d H:i:s'); // Get current date and time
        $footerTextCell->addText('This report is generated on ' . $currentDateTime, ['size' => 10]); // Updated footer text

        // Cell for page number with padding
        $footerPageNumberCell = $footerTable->addCell(1500, ['cellMargin' => 0]); // Added cellMargin for spacing
        $footerPageNumberCell->addPreserveText('Page {PAGE} of {NUMPAGES}', null, ['alignment' => 'right']);

        $fileName = 'codes' . time() . '.docx';
        $filePath = storage_path('codes/' . $fileName);
        $phpWord->save($filePath, 'Word2007');

        return [
            "filename" => $fileName,
            "filepath" => $filePath,
        ];
    } catch (\Exception $exp) {
        throw new \Exception($exp->getMessage() . " " . $exp->getLine() . " " . $exp->getFile());
    }
}




    function createSalesReport(User $user, $fromDate, $toDate) {
        try {
            $company = $user->company;
            
    
            $fontColor = '000000'; // Default color
             $logoPath = public_path('assets/img/logo-old.png'); // Default logo path
            $invoiceSetting = UserInvoiceSetting::where("company_id", $company->id)->first();
    
            // Add company logo
            if ($invoiceSetting && $user->hasSubscription(Membership::SUBSCRIPTION_INVOICE)) {
                if ($invoiceSetting->logo) {
                    $logoPath = storage_path(str_replace("storage/", "", $invoiceSetting->logo)); 
                }
            }
            
            
               $phpWord = new PhpWord();
            
            // Set default font properties for the whole document
            $phpWord->setDefaultFontName('Cambria');
           
            $section = $phpWord->addSection();
            $boldFontStyle = ['bold' => true];
            $normalFontStyle = ['bold' => false];
            $centeredParagraphStyle = ['alignment' => 'center'];
            
            // Define styles
            $logoCellStyle = ['valign' => 'center'];
            $companyInfoStyle = ['size' => 20, 'bold' => true, 'color' => $fontColor];
            $normalTextStyle = ['size' => 12]; // Normal text style for addresses and contacts
            
            // Create a table with 3 columns and center alignment in the body (not header)
            $table = $section->addTable(['borderColor' => 'ffffff', 'borderSize' => 0, 'cellMargin' => 50, 'alignment' => 'center']);
            
            // Add a row to the table
            $table->addRow();
            
            // First column: Company Logo
            $logoCell = $table->addCell(1200, $logoCellStyle);
            if (isset($logoPath) && file_exists($logoPath)) {
                $logoCell->addImage($logoPath, ['width' => 60, 'height' => 60]); // Adjust path and size
            }
            
            // Second column: Company Information
            $infoCell = $table->addCell(3500);
            if (isset($company)) {
                $infoCell->addText($company->name, $companyInfoStyle);
                $infoCell->addText(@$company->address, $normalTextStyle);
                $infoCell->addText(@$user->phone, $normalTextStyle);
                $infoCell->addText(@$user->email, $normalTextStyle);
            }
            
            $section->addTextBreak(2);
    
            // Add Sales Report title
            $section->addText('Sales Report', ['bold' => true, 'size' => 18], $centeredParagraphStyle);
    
            // Add Sales Data
            $tableStyle = [
                'borderSize' => 0,
                'cellMargin' => 80,
                'width' => 10000,
                'unit' => TblWidth::TWIP,
                'alignment' => 'center',
            ];
            $estimateService = new EstimateService();
            $data = $estimateService->getSalesReport($user, $fromDate, $toDate);
    
            $table = $section->addTable($tableStyle);
            $table->addRow();
            $table->addCell(1000)->addText('Date', ['bold' => true, 'color' => $fontColor]);
            $table->addCell(1000)->addText('Quote', ['bold' => true, 'color' => $fontColor]);
            $table->addCell(1000)->addText('Customer', ['bold' => true, 'color' => $fontColor]);
            $table->addCell(1700)->addText('Address', ['bold' => true, 'color' => $fontColor]);
            $table->addCell(200)->addText('Amount', ['bold' => true, 'color' => $fontColor]);
    
            foreach ($data as $estimate) {
                $table->addRow();
                $cost = "$" . optional($estimate)->grand_total;
                $date = optional($estimate)->created_at->format("Y-m-d");
                $table->addCell(1000)->addText($date, ['color' => $fontColor]);
                $table->addCell(1000)->addText(optional($estimate)->key, ['color' => $fontColor]);
                $table->addCell(1000)->addText(optional($estimate->customer)->name, ['color' => $fontColor]);
                $table->addCell(1700)->addText(optional($estimate->customer)->address, ['color' => $fontColor]);
                $table->addCell(200)->addText($cost, ['color' => $fontColor]);
            }
    
            // Add footer with two cells: one for text and one for page number
            $footer = $section->addFooter();
            $footerTable = $footer->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 50]); // Adjust cellMargin for spacing
            $footerTable->addRow();
    
            $footerTextCell = $footerTable->addCell(8000, ['cellMargin' => 100]); // Added cellMargin for spacing
            $currentDateTime = date('Y-m-d H:i:s'); // Get current date and time
            $footerTextCell->addText('This report is generated on ' . $currentDateTime, ['size' => 10]); // Updated footer text
    
            // Cell for page number with padding
            $footerPageNumberCell = $footerTable->addCell(1500, ['cellMargin' => 0]); // Added cellMargin for spacing
            $footerPageNumberCell->addPreserveText('Page {PAGE} of {NUMPAGES}', null, ['alignment' => 'right']);
    
            // Save the document
            $fileName = 'sales_report_' . time() . '.docx';
            $filePath = storage_path('codes/' . $fileName);
            $phpWord->save($filePath, 'Word2007');
    
            return [
                "filename" => $fileName,
                "filepath" => $filePath,
            ];
        } catch (\Exception $exp) {
            throw new \Exception($exp->getMessage() . " " . $exp->getLine() . " " . $exp->getFile());
        }
    }
    
    
    public function getSalesReportData(User $user, $fromDate, $toDate) {
        $company = $user->company;
        $reportData = [];
        
        // Default logo path (use relative path)
        $logoPath = 'assets/img/logo-old.png'; // Default logo path
        $invoiceSetting = UserInvoiceSetting::where("company_id", $company->id)->first();
    
        // Add company logo if available
        if ($invoiceSetting && $user->hasSubscription(Membership::SUBSCRIPTION_INVOICE)) {
            if ($invoiceSetting->logo) {
                // Set logo to relative path
                $logoPath = $invoiceSetting->logo; // Adjust based on where your logo is stored
            }
        }
    
        // Prepare company details
        $reportData['company'] = [
            'logo' => $logoPath, // Use relative path
            'name' => $company->name,
            'address' => $company->address,
            'phone' => $user->phone,
            'email' => $user->email,
        ];
    
        $estimateService = new EstimateService();
        $salesData = $estimateService->getSalesReport($user, $fromDate, $toDate);
        
   
        // Structure the sales data
        $reportData['sales'] = [];
        foreach ($salesData as $estimate) {
            $reportData['sales'][] = [
                'date' => optional($estimate)->created_at->format("Y-m-d"),
                'quote_key' => optional($estimate)->key,
                'customer' => [
                    'name' => optional($estimate->customer)->name,
                    'address' => optional($estimate->customer)->address,
                ],
                'amount' => optional($estimate)->grand_total,
            ];
        }
    
        return $reportData;
    }
    
    






}