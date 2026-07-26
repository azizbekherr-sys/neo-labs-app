<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticlesController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::query()->orderByDesc('id');
        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($sub) use ($q) {
                $like = "%{$q}%";
                $sub->where('title_uz', 'like', $like)
                    ->orWhere('title_ru', 'like', $like)
                    ->orWhere('title_en', 'like', $like)
                    ->orWhere('description_uz', 'like', $like)
                    ->orWhere('description_ru', 'like', $like)
                    ->orWhere('description_en', 'like', $like);
            });
        }
        $articles = $query->paginate(9)->withQueryString();

        return view('pages.articles', compact('articles'));
    }

    public function show(Article $article)
    {
        // Increment views counter on each visit
        try {
            DB::table('articles')->where('id', $article->id)->increment('views');
        } catch (\Throwable $e) {
            // noop
        }
        return view('pages.article-show', compact('article'));
    }
}


