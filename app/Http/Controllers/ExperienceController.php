<?php

namespace App\Http\Controllers;

use App\Services\PortfolioContent;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    public function __invoke(PortfolioContent $content): View
    {
        return view('pages.experience', ['experiences' => $content->experiences()]);
    }
}
