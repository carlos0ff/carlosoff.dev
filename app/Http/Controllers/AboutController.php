<?php

namespace App\Http\Controllers;

use App\Services\PortfolioContent;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function __invoke(PortfolioContent $content): View
    {
        return view('pages.about', [
            'about' => $content->about(),
            'stack' => $content->stack(),
            'experiences' => $content->experiences(),
            'education' => $content->education(),
            'certifications' => $content->courses(),
        ]);
    }
}
