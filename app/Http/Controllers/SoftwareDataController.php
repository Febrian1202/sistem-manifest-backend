<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SoftwareDataController extends Controller
{
    //
    public function index()
    {
        return view('pages.admin.softwares');
    }
}
