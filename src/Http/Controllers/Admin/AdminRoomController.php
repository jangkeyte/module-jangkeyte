<?php

namespace Modules\Authetication\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\MeetingRoom\src\Models\Room;

class AdminRoomController extends Controller
{
    public function index()
    {
        $rooms = Room::get();
        return view('Authetication::room.index', compact('rooms'));
    }

    public function create()
    {
        return view('Authetication::room.create');
    }

    public function store(Request $request)
    {
        $request->validate([            
            'heading' => 'required',
            'slug' => 'required|alpha_dash|unique:rooms',
            'short_description' => 'required',
            'description' => 'required',
            'photo' => 'required|image|mimes:jpg,jpeg,png,gif',
        ]);

        $obj = new Room();
        
        $ext = $request->file('photo')->extension();
        $final_name = 'room_' . time() . '.' . $ext;
        $request->file('photo')->move(public_path('uploads/'), $final_name);

        $obj->photo = $final_name;
        $obj->heading = $request->heading;
        $obj->slug = $request->slug;
        $obj->short_description = $request->short_description;
        $obj->description = $request->description;
        $obj->total_view = 0;
        $obj->title = $request->title;
        $obj->meta_description = $request->meta_description;
        $obj->save();

        return redirect()->route('admin_room')->with('success', __('Data is saved successfully.'));
    }
    
    public function edit($id)
    {
        //dd(auth()->user()->can('browse-room'));
        $room_single = Room::where('id', $id)->first();
        return view('Authetication::room.edit', compact('room_single'));
    }

    public function update(Request $request)
    {
        $obj = Room::where('id', $request->id)->first();

        $request->validate([               
            'heading' => 'required',
            'slug' => ['required', 'alpha_dash', Rule::unique('rooms')->ignore($request->id)],
            'short_description' => 'required',
            'description' => 'required',
        ]);

        if($request->hasFile('photo')) {
            $request->validate([
                'photo' => 'image|mimes:jpg,jpeg,png,gif',
            ]);

            if(file_exists(public_path('uploads/' . $obj->photo))) {
                unlink(public_path('uploads/' . $obj->photo));
            }

            $ext = $request->file('photo')->extension();
            $final_name = 'room_' . time() . '.' . $ext;

            $request->file('photo')->move(public_path('uploads/'), $final_name);

            $obj->photo = $final_name;
        }

        $obj->heading = $request->heading;
        $obj->slug = $request->slug;
        $obj->short_description = $request->short_description;
        $obj->description = $request->description;
        $obj->title = $request->title;
        $obj->meta_description = $request->meta_description;
        $obj->update();

        return redirect()->route('admin_room')->with('success', __('Data is updated successfully.'));
    }
    
    public function delete($id)
    {
        $room_single = Room::where('id', $id)->first();
        unlink(public_path('uploads/' . $room_single->photo));
        Room::where('id', $id)->delete();
        return redirect()->route('admin_room')->with('success', __('Data is deleted successfully.'));
    }
}
