<?php

namespace App\Http\Controllers\Modules\PushNotification;

use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\DatatableTrait;
use App\Http\Controllers\ModuleController;
use App\Models\PushNotification;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use App\Notifications\FirebasePushNotification;
use App\Services\FirebaseService;


class PushNotificationController extends ModuleController
{
    use DatatableTrait;

    public function __construct()
    {
        parent::__construct();
        $this->setModuleName('pushNotification');
    }

    public function index()
    {
        $this->injectDatatable();
        return $this->view('index');
    }

    public function add():View
    {
        $contracts = User::where("type", "=", 3)->where("status", "=", 1)->where("device_token", "!=", "")->get();
        return $this->view('add',['contractors'=>$contracts]);
    }

    public function create(Request $request, FirebaseService $firebaseService)
    {
     
        
        Validator::make($request->all(), [
            'title' => 'required|string',
            'message' => 'required|string',
            'contractor' => 'required|array|min:1',
            'contractor.*' => 'required|integer|min:1',
        ], [
            "contractor.required" => "Please select at least one."
        ])->validate();
    
    
    
        // $participants = User::where("type", 3)
        //     ->where("status", 1)
        //     ->whereNotNull('device_token')
        //     ->where('device_token', '!=', '')
        //     ->whereIn("id", $request->input("contractor"))
        //     ->get(['id', 'device_token'])
        //     ->toArray();
            
            
    
        // if (empty($participants)) {
        //     return redirect()->route('voyager.push-notifications.create')->with('error', 'No contractors found.');
        // }
    
        // $tokens = array_column($participants, 'device_token');
        // $notificationResponse = $firebaseService->sendNotificationToMultiple($tokens, $request->input('title'), $request->input('message'));
        
        
           $participants = User::where("type", 3)
    ->where("status", 1)
    ->whereIn("id", $request->input("contractor"))
    ->with(['devices' => function ($query) {
        $query->whereNotNull('device_token')->where('device_token', '!=', '');
    }])
    ->get();

if ($participants->isEmpty()) {
    return redirect()->route('voyager.push-notifications.create')->with('error', 'No contractors found.');
}

// Collect all the participants that have valid device tokens
$validParticipants = $participants->filter(function ($participant) {
    return $participant->devices->isNotEmpty(); // Ensure the user has devices with valid tokens
});

// Ensure there are valid participants to send notifications
if ($validParticipants->isEmpty()) {
    return redirect()->route('voyager.push-notifications.create')->with('error', 'No valid device tokens found.');
}

// Send notifications to each valid participant
foreach ($validParticipants as $participant) {
    // Send the notification using the notify method
    $participant->notify(new FirebasePushNotification(
        $request->input('title'),
        $request->input('message')
    ));
}

// Redirect with a success message
return redirect()->route('voyager.push-notifications.create')->with('success', 'Notifications sent successfully.');
    
        // $newNotification = new PushNotification();
        // $newNotification->title = $request->input('title');
        // $newNotification->message = $request->input('message');
        // $newNotification->contractors = json_encode(array_column($participants, 'id'));
        // $newNotification->fcm_response = json_encode($notificationResponse);
        // $newNotification->save();
    
        if (!empty($request->input('saveClose'))) {
            return redirect()->route($this->mRoute('home'))->with('success', 'Push notification sent successfully!');
        }
    
        return redirect()->back()->with('success', 'Push notification sent successfully!');
    }



    protected function getDataTableColumns(): array
    {
        return [
            ["data" => "id"],
            ["data" => "title"],
            ["data" => "message"],
            ["data" => "contractors", "onAction"=>function($row){
            $contractors = json_decode($row["contractors"], true);
                return count($contractors);
            }],

            ["data" => "create_at", "onAction" => function ($row) {
                return date("Y-m-d H:m", strtotime($row["created_at"]));
            }],
        ];
    }

    protected function getModuleTable() : string
    {
        return (new PushNotification())->getTable();
    }

    protected function getDataTableRows(): array
    {
        return PushNotification::orderBy('id', 'DESC')->get()->toArray();
        //return HomeCards::where('is_archive', 0)->where("type", "!=", "APP-USER")->orderBy('id', 'DESC')->get()->toArray();
    }
}
