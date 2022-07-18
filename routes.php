<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Router;
use App\Post;
use Illuminate\Support\Str;
use Wruczek\PhpFileCache\PhpFileCache;

/** @var $router Router */

$site = config("site");

$router->name("home")->get("/", function () use ($site) {
    return view()
        ->make("home", [
            "posts" => posts(),
            "site" => $site,
        ])
        ->render();
});

$router->name("sitemap")->get("/sitemap.xml", function () use ($site) {
    return view()->make("sitemap", [
        "posts" => posts(100),
        "site" => $site,
    ]);
});

$router->name("image")->get("/{slug}.html", function ($slug) use ($site) {
    if (in_array($slug, pages())) {
        return view()->make($slug, [
            "site" => $site,
            "page" => $slug,
        ]);
    }

    $cache = post($slug);

    if ($cache->isHit()) {
        $post = $cache->get();

        return view()->make("image", [
            "keyword" => $post->keyword->name,
            "sentences" => $post->keyword->sentences,
            "images" => $post->keyword->images->toArray(),
            "related" => related($post),
            "post" => $post,
            "site" => $site,
        ]);
    }

    return 'data is loading, please <a href="' .
        route("home") .
        '">return to homepage</a>';
});

$router
    ->name("single")
    ->get("/{slug}/{image_slug}.html", function ($slug, $image_slug) use (
        $site
    ) {
        $cache = post($slug);

        if ($cache->isHit()) {
            $post = $cache->get();

            foreach ($post->keyword->images->toArray() as $image) {
                if (
                    preview_url($image, $post) ===
                    route("single", [
                        "slug" => $slug,
                        "image_slug" => $image_slug,
                    ])
                ) {
                    return view()->make("single", [
                        "keyword" => $image["title"],
                        "sentences" => $post->keyword->sentences,
                        "images" => $post->keyword->images->toArray(),
                        "related" => related($post),
                        "post" => $post,
                        "site" => $site,
                        "image" => $image,
                    ]);
                }
            }
        }

        return 'single image is loading, please <a href="' .
            route("home") .
            '">return to homepage</a>';
    });

if (config("autogrow")) {
    scrape();
}