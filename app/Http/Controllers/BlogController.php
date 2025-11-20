<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $articles = collect([]);

        return view('blog.index', compact('articles'));
    }

    public function show(string $slug): View
    {
        return view('blog.show', compact('slug'));
    }
}
