<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Events\LocationUpdated;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        return view('location-tracker');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required',
            'longitude' => 'required'
        ]);

        $userId = session()->getId();

        broadcast(new LocationUpdated(
            $userId,
            $validated['latitude'],
            $validated['longitude']
        ));

        return response()->json([
            'success' => true,
            'message' => 'Location Updated'
            ]);
    }
}
