<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FilesLibrayController extends Controller
{
    public function filesLibrary(Request $request)
    {
        return view('/files.library');
    }
}
