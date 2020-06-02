<?php

use App\Article;
use App\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * 首页的文章列表
 * @return collection([article]) 包含分页信息和移动ＶＵＥ等优化的文章列表
 */
function indexArticles()
{
    $qb = Article::from('articles')
        ->has('user')
        ->has('category')
        ->exclude(['body', 'json'])
        ->where('status', '>', 0)
        ->whereNull('source_url')
        ->whereNotNull('category_id')
        ->latest('updated_at');

    $total    = $qb->count();
    $articles = $qb->offset((request('page', 1) * 10) - 10)
        ->take(7)
        ->get();

    //移动端，用简单的分页样式
    if (isMobile()) {
        $articles = new Paginator($articles, 10);
        $articles->hasMorePagesWhen($total > request('page') * 10);
    } else {
        $articles = new LengthAwarePaginator($articles, $total, 10);
    }
    return $articles;
}

//FIXME: 重构到 haxibiao-content （article, post, commentable）
// haxibiao-category (categorized, tagable)
function get_categories($full = 0, $type = 'article', $for_parent = 0)
{
    $categories = [];
    if ($for_parent) {
        $categories[0] = null;
    }
    $category_items = Category::where('type', $type)->orderBy('order', 'desc')->get();
    foreach ($category_items as $item) {
        if ($item->level == 0) {
            $categories[$item->id] = $full ? $item : $item->name;
            if ($item->has_child) {
                foreach ($category_items as $item_sub) {
                    if ($item_sub->parent_id == $item->id) {
                        $categories[$item_sub->id] = $full ? $item_sub : ' -- ' . $item_sub->name;
                        foreach ($category_items as $item_subsub) {
                            if ($item_subsub->parent_id == $item_sub->id) {
                                $categories[$item_subsub->id] = $full ? $item_subsub : ' ---- ' . $item_subsub->name;
                            }
                        }
                    }
                }
            }
        }
    }
    $categories = collect($categories);
    return $categories;
}

function get_carousel_items($category_id = 0)
{
    $carousel_items = [];
    if (isMobile()) {
        return $carousel_items;
    }
    $query = Article::orderBy('id', 'desc')
        ->where('image_top', '<>', '')
        ->where('is_top', 1);
    if ($category_id) {
        $query = $query->where('category_id', $category_id);
    }
    $top_pic_articles = $query->take(5)->get();
    $carousel_index   = 0;
    foreach ($top_pic_articles as $article) {
        $item = [
            'index'       => $carousel_index,
            'id'          => $article->id,
            'title'       => $article->title,
            'description' => $article->description,
            'image_url'   => $article->cover,
            'image_top'   => $article->image_top,
        ];
        $carousel_items[] = $item;
        $carousel_index++;
    }
    return $carousel_items;
}
