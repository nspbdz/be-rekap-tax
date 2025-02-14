<?php

namespace App\Http\Controllers;

use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    public function index()
    {
        $data=Test::get();

        return view('welcome', ['data' => $data]);
    }

    public function create()
    {

        return view('test_create');
    }

    public function store(Request $request)
    {
        DB::table('tests')->insert([
            'name' => $request->name,
        ]);
        return redirect('/test');

    }
}
