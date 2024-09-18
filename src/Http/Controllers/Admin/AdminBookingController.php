<?php

namespace Modules\Authetication\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\MeetingRoom\src\Models\Booking;

class AdminBookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::get();
        return view('Authetication::booking.index', compact('bookings'));
    }

    public function create()
    {
        return view('Authetication::booking.create');
    }

    public function store(Request $request)
    {
        $request->validate([            
            'heading' => 'required',
            'slug' => 'required|alpha_dash|unique:bookings',
            'short_description' => 'required',
            'description' => 'required',
            'photo' => 'required|image|mimes:jpg,jpeg,png,gif',
        ]);

        $obj = new Booking();
        
        $ext = $request->file('photo')->extension();
        $final_name = 'booking_' . time() . '.' . $ext;
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

        return redirect()->route('admin_booking')->with('success', __('Data is saved successfully.'));
    }
    
    public function edit($id)
    {
        //dd(auth()->user()->can('browse-booking'));
        $booking_single = Booking::where('id', $id)->first();
        return view('Authetication::booking.edit', compact('booking_single'));
    }

    public function update(Request $request)
    {
        $obj = Booking::where('id', $request->id)->first();

        $request->validate([               
            'heading' => 'required',
            'slug' => ['required', 'alpha_dash', Rule::unique('bookings')->ignore($request->id)],
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
            $final_name = 'booking_' . time() . '.' . $ext;

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

        return redirect()->route('admin_booking')->with('success', __('Data is updated successfully.'));
    }
    
    public function delete($id)
    {
        $booking_single = Booking::where('id', $id)->first();
        unlink(public_path('uploads/' . $booking_single->photo));
        Booking::where('id', $id)->delete();
        return redirect()->route('admin_booking')->with('success', __('Data is deleted successfully.'));
    }
}
