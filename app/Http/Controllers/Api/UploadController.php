<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Security\VirusScanner;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $allowed = (array) config('security.uploads.allowed_extensions', []);
        $max = (int) config('security.uploads.max_size', 10240);
        $mimes = implode(',', $allowed);
        $request->validate([
            'file' => ['required','file','max:'.$max,'mimes:'.$mimes],
            'collection' => ['nullable','string'],
        ]);

        $collection = $request->input('collection', 'default');
        $dir = 'uploads/'.(Str::slug($collection) ?: 'default');
        Storage::disk('local')->makeDirectory($dir);
        $file = $request->file('file');
        if (config('security.uploads.scan_for_viruses')) {
            (new VirusScanner())->scan($file);
        }
        $ext = strtolower($file->getClientOriginalExtension());
        $name = (string) Str::uuid().($ext ? '.'.$ext : '');
        $path = Storage::disk('local')->putFileAs($dir, $file, $name);
        $fullPath = storage_path('app/'.$path);

        return response()->json([
            'status' => 'success',
            'path' => $path,
            'disk' => 'local',
            'full_path' => $fullPath,
            'filename' => $name,
        ]);
    }
}
