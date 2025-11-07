<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Display the specified page.
     */
    public function show(string $slug): View
    {
        $page = Page::where('slug', $slug)
            ->published()
            ->firstOrFail();

        return view('pages.show', [
            'page' => $page,
        ]);
    }
}
