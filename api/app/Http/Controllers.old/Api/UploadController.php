<?php

namespace App\Http\Controllers\Api;

use App\Entities\Profile;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function upload(Request $request) {
        $dir = date('Y-m-d');

        $path = $request->file('file')->store('attachment/' . $dir, 'public');

        return response()->json([
            'success' => true,
            'url' => env('APP_URL').Storage::url($path)
        ]);
    }
}
