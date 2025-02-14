<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class jodiController extends Controller
{
    public function index()
    {
        // Mengambil semua data pengguna dari model

        // Mengirim data pengguna ke view
        return view('welcome');
        // return view('welcome', ['users' => $users]);
    }
}
