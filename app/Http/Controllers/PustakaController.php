<?php

namespace App\Http\Controllers;

use App\Models\Pustaka;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PustakaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Pustaka::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'body' => 'required|string'
        ]);

        $posting = [
            'title' => $validated['title'],
            'body' => $validated['body']
        ];

        Pustaka::create($posting);

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil disimpan',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Pustaka $pustaka)
    {
        return $pustaka;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pustaka $pustaka)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string',
            'body' => 'sometimes|required|string'
        ]);

        $pustaka->update($validated);
        return $pustaka;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pustaka $pustaka)
    {
        $pustaka->delete();
        return response()->json(['message' => 'Post deleted']);
    }

    public function buangData(Request $request)
    {
        $validated = $request->all();

        $body = $validated['body'];

        $filename = 'data/dump.json';

        Storage::put($filename, json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return response()->json([
            'message' => 'Data berhasil disimpan.',
            'path' => Storage::path($filename)
        ]);
    }

}
