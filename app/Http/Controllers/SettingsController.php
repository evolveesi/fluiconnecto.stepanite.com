<?php

namespace App\Http\Controllers;

use App\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index(){
        $settings = Settings::find(1);
        return view('pages.core.settings',[
            'settings'=>$settings
        ]);
    }
    public function store(Request $request){
        $settings = Settings::find($request->id);
        $settings->bank = $request->bank;
        $settings->branch = $request->branch;
        $settings->local_curr_account_no = $request->local_curr_account_no;
        $settings->foreign_curr_account_no = $request->foreign_curr_account_no;
        $settings->swift_code = $request->swift_code;
        $settings->tel = $request->tel;
        $settings->address = $request->address;
        $settings->email = $request->email;
        $settings->company_name = $request->company_name;

        $settings->account_name = $request->account_name;
        $settings->branch_code = $request->branch_code;
        $settings->sort_code = $request->sort_code;

        if (is_file($request->logo)){
            $media_url = $request->logo;
            $event_file_name = trim(str_replace('-', '_', "business".'_'.time().uniqid().'.'.$media_url->getClientOriginalExtension()));
            $media_url->storeAs('public/',$event_file_name);
            $settings->logo = $event_file_name;
        }
        $settings->update();
        return redirect()->back()->with('status', 'Company Details Successfully Updated!');
    }
}
