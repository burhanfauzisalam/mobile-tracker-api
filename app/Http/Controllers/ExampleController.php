<?php

namespace App\Http\Controllers;

class ExampleController extends Controller
{
    /**
     * Display the realtime tracking dashboard view.
     */
    public function __invoke()
    {
        return view('example');
    }
}
