<?php


namespace App\Http\Controllers\Modules\ProfileSettings;


use App\Helpers\Helper;
use App\Http\Controllers\ModuleController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileSettingsController extends ModuleController
{
    public function __construct()
    {
        parent::__construct();
        $this->setModuleName('profileSettings');
    }
    public function index()
    {
        $user = Auth::user()->toArray();
        return $this->view('index',['data'=>$user]);
    }
    public function update(Request $request)
    {

        Validator::make($request->all(), [
            'name' => 'required|string',
            'phone' => 'required',
            'email' => 'required|string',
            'password' => 'required_with:password_confirmation|same:password_confirmation|sometimes|nullable|between:8,20',
            'password_confirmation' => 'sometimes|nullable|between:8,20'
        ])->validate();

        $cdata = $request->except('_token', '_method');
        unset($cdata['password_confirmation']);
        if(empty($cdata['password'])){
            unset($cdata['password']);
        }else{
            $cdata['password'] = Hash::make($cdata['password']);

        }


        User::where('id', $cdata['id'])->update($cdata);

        return redirect()->route($this->mRoute('home'))->with('success', 'Profile Updated Successfully!');

    }

    protected function getModuleTable(): string
    {
        return 'users';
    }
}
