<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BitgetController;

class FormController extends Controller
{
    public function index(Request $request, BitgetController $bitget)
    {
        // Handle the form submission
        if ($request->isMethod('post')) {
            $bitget->placeSpotOrder($request);
            return redirect()->back()->with('success', 'Form submitted successfully!');
        }

        // If it's a GET request, just show the form view
        return view('form');
    }
}
