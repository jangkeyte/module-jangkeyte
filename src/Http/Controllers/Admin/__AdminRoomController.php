<?php

namespace Modules\Authetication\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MeetingRoom\src\Models\Room;
use Modules\MeetingRoom\src\Models\Booking;

class AdminRoomController extends Controller
{
    public function index() 
    {
        $rooms = Room::where('status', 1)->paginate(10);
        return view('Authetication::room.room_index', compact('rooms'));
    }

    public function rooms_detail($id) 
    {
        $rooms_detail = Room::with('rLayout')->where('id', $id)->first();
        return view('Authetication::room.room_detail', compact('rooms_detail'));
    }

    public function rooms_bookings($id) 
    {
        $rooms_detail = Room::with('rLayout')->where('id', $id)->first();
        $rooms_bookings = Booking::with('rSlot', 'rEquipment', 'rFoodDrink')->where('room_id', $id)->get();
        return view('Authetication::room.room_bookings', compact('rooms_bookings'));
    }
    
    public function rooms_delete($id)
    {
        //RoomLayout::where('room_id', $id)->delete();
        Booking::where('room_id', $id)->delete();
        
        $booking_list = Booking::where('room_id', $id)->get();
        foreach($booking_list as $item) {
            Booking::where('id', $item->id)->delete();
        }

        $room_data = Room::where('id', $id)->first();
        if($room_data->image != null) {
            if(file_exists(public_path('uploads/' . $room_data->image))) {
                unlink(public_path('uploads/' . $room_data->image));
            }
        }
        Room::where('id', $id)->delete();   
        return redirect()->back()->with('success', __('Data is deleted successfully.'));
    }
    
}
