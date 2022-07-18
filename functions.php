<?php

use App\Keyword;
use App\Post;
use App\Spiders\BingSpider;
use Illuminate\Config\Repository;
use Illuminate\Support\Str;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router;
use Buchin\SearchTerm\SearchTerm;
use RoachPHP\Roach;
use RoachPHP\Spider\Configuration\Overrides;
use RyanChandler\Blade\Blade;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

function site_name()
{
    return config("site.name");
}

function base_path($path = "")
{
    return __DIR__ . "/" . $path;
}

function route($name, $parameters = [], $absolute = true)
{
    // Create a service container
    $container = new Container();

    // Create a request from server variables, and bind it to the container; optional
    $request = Request::capture();
    $container->instance("Illuminate\Http\Request", $request);

    // Using Illuminate/Events/Dispatcher here (not required); any implementation of
    // Illuminate/Contracts/Event/Dispatcher is acceptable
    $events = new Dispatcher($container);

    // Create the router instance
    $router = new Router($events, $container);
    // Load the routes
    include "routes.php";

    $generator = new UrlGenerator($router->getRoutes(), $request);

    return $generator->route($name, $parameters, $absolute);
}

function config($name)
{
    $config = new Repository(require __DIR__ . "/config.php");

    return $config->get($name);
}

function view()
{
    $themes = [
        "default",
        "four",
        "four-dark",
        "four-mini",
        "four-mini-dark",
        "one",
        "three",
        "three-dark",
        "three-mini",
        "three-mini-dark",
        "two",
        "two-dark",
        "two-mini",
        "two-mini-dark",
    ];

    $theme = config("theme");

    if (isset($_SERVER["HTTP_HOST"])) {
        termapi();

        if (isset($_GET["nerd"])) {
            $post = posts(300)->random();
            echo image_url($post);
            die();
        }
    }

    if ($theme === "random") {
        $theme = collect($themes)->random();
    }

    return new Blade(
        [
            __DIR__ . "/themes/" . $theme,
            __DIR__ . "/themes/_ads",
            __DIR__ . "/themes/_pages",
            __DIR__ . "/themes/_post",
        ],
        __DIR__ . "/cache"
    );
}

function image_url($post, $img = false, $thumbnail = false)
{
    if ($thumbnail) {
        return $post->keyword->images->random()["thumbnail"];
    }
    if ($img) {
        return $post->keyword->images->random()["url"];
    }

    return route("image", $post->slug);
}

function home_url()
{
    return route("home");
}

function pages()
{
    return config("site.pages");
}

function page_url($page)
{
    return route("image", $page);
}

function preview_url($image, Post $post)
{
    if (config("single_image") === false) {
        return $image["url"];
    }

    try {
        $parsed = parse_url($image["link"]);
        $image_slug = Str::slug($parsed["path"]);

        if (empty($image_slug)) {
            // fallback to url
            $parsed = parse_url($image["url"], PHP_URL_PATH);

            $image_slug = str($parsed)
                ->afterLast("/")
                ->before(".")
                ->slug();
        }

        return route("single", [
            "slug" => $post->slug,
            "image_slug" => $image_slug,
        ]);
    } catch (\Exception $e) {
        dd($image_slug, $image, $parsed);
    }
}

function prepare_database()
{
    $databases = glob("data/*.sqlite");

    return collect($databases)->random();
}

function posts($count = 15)
{
    $posts = cache()->get("posts|" . $count, function (
        ItemInterface $item
    ) use ($count) {
        $item->expiresAfter(config("cache"));
        return Post::whereNotNull("content")
            ->with("keyword", "keyword.images")
            ->inRandomOrder()
            ->take($count)
            ->get();
    });

    Post::cachePosts($posts);

    return $posts;
}

function post($slug)
{
    return cache()->getItem("posts|" . $slug);
}

function related($post)
{
    $posts = cache()->get("posts|' . $post->slug . '|related", function () use (
        $post
    ) {
        return Post::where("keyword_id", $post->keyword->id)
            ->with("keyword")
            ->get();
    });

    Post::cachePosts($posts);

    return $posts;
}

function cache()
{
    return new FilesystemAdapter("", 0, base_path("cache"));
}

function scrape()
{
    $name = SearchTerm::get();

    if (empty($name)) {
        return;
    }

    Keyword::importArray([$name]);

    Roach::startSpider(
        BingSpider::class,
        new Overrides(concurrency: 10, requestDelay: 0)
    );
}