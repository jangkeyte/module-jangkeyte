<?php

namespace Modules\Authetication\src\Http\Controllers\Admin;

use Modules\JobPortal\src\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\JobPortal\src\Models\Setting;

class AdminSettingController extends Controller
{
    public function index()
    {
        $settings = Setting::where('id', 1)->first();
        return view('Authetication::admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $obj = Setting::where('id', 1)->first();

        $request->validate([               
            'copyright_text' => 'required',
        ]);

        if($request->hasFile('logo')) {
            $request->validate([
                'logo' => 'image|mimes:jpg,jpeg,png,gif',
            ]);
            
            if(file_exists(public_path('uploads/' . $obj->logo))) {
                unlink(public_path('uploads/' . $obj->logo));
            }

            $ext = $request->file('logo')->extension();
            $final_name = 'logo' . '.' . $ext;

            $request->file('logo')->move(public_path('uploads/'), $final_name);

            $obj->logo = $final_name;
        }

        if($request->hasFile('favicon')) {
            $request->validate([
                'favicon' => 'image|mimes:jpg,jpeg,png,gif',
            ]);

            if(file_exists(public_path('uploads/' . $obj->favicon))) {
                unlink(public_path('uploads/' . $obj->favicon));
            }

            $ext = $request->file('favicon')->extension();
            $final_name = 'favicon' . '.' . $ext;

            $request->file('favicon')->move(public_path('uploads/'), $final_name);

            $obj->favicon = $final_name;
        }
          
        $obj->top_bar_phone = $request->top_bar_phone;
        $obj->top_bar_email = $request->top_bar_email;
        $obj->footer_phone = $request->footer_phone;
        $obj->footer_email = $request->footer_email;
        $obj->footer_address = $request->footer_address;
        $obj->facebook = $request->facebook;
        $obj->twitter = $request->twitter;
        $obj->pinterest = $request->pinterest;
        $obj->linkedin = $request->linkedin;
        $obj->instagram = $request->instagram;
        $obj->copyright_text = $request->copyright_text;
        $obj->update();

        return redirect()->back()->with('success', __('Data is updated successfully.'));
    }
}
