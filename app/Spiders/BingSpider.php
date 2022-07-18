<?php

namespace App\Spiders;

use App\Image;
use App\Keyword;
use App\Post;
use Buchin\Badwords\Badwords;
use Carbon\Carbon;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;

class BingSpider extends BasicSpider
{
    public $cli;

    public $count = 0;

    public array $downloaderMiddleware = [
        //        ExecuteJavascriptMiddleware::class
    ];

    public int $concurrency = 10;
    public int $requestDelay = 0;

    public array $extensions = [
        //        StatsCollectorExtension::class,
    ];

    public function scrapeImages(Response $response)
    {
        $results = $response->filter(".imgpt")->each(function ($node) {
            $json = @json_decode($node->filter("a.iusc")->attr("m"));
            try {
                $size = $node->filter("div.img_info span.nowrap")->html();
            } catch (\InvalidArgumentException $e) {
                // I guess its InvalidArgumentException in this case
                $size = "0 x 0";
            }

            return [
                "url" => $json->murl,
                "link" => $json->purl,
                "title" => str_replace(["", "", " ..."], "", $json->t),
                "thumbnail" => $json->turl,
                "size" => $size,
                "desc" => $json->desc,
            ];
        });

        $results = collect($results)
            ->unique("url")
            ->toArray();

        $results = $this->postProcessImage($results);

        return $results;
    }

    public function postProcessImage($raw_images)
    {
        $images = [];
        foreach ($raw_images as $raw_image) {
            $image = $this->postProcessSingleImage($raw_image);

            $images[] = $image;
        }

        return $images;
    }

    public function postProcessSingleImage($raw_image, $delimiter = "·")
    {
        $raw_image["filetype"] = trim(
            @explode($delimiter, $raw_image["size"])[1]
        );
        $raw_image["filetype"] =
            $raw_image["filetype"] == "jpeg" ? "jpg" : $raw_image["filetype"];
        $raw_image["filetype"] =
            $raw_image["filetype"] == "animatedgif"
                ? "gif"
                : $raw_image["filetype"];

        $raw_image["width"] = explode(
            " x ",
            @explode($delimiter, $raw_image["size"])[0]
        )[0];
        $raw_image["height"] = explode(
            " x ",
            @explode($delimiter, $raw_image["size"])[0]
        )[1];
        $raw_image["domain"] = parse_url($raw_image["link"], PHP_URL_HOST);

        return $raw_image;
    }

    public function scrapeKeywords(Response $response, $keyword = null): array
    {
        $keywords = $response
            ->filter(".ent_img img.rms_img")
            ->each(function ($node) use ($keyword) {
                return $node->attr("alt");
            });

        $keywords = array_merge(
            $keywords,
            $response
                ->filter("a > div.cardInfo > div > strong")
                ->each(function ($node) {
                    return $node->text();
                })
        );

        return array_merge(
            $keywords,
            $response
                ->filter("ol.items li.item .card a.cardToggle")
                ->each(function ($node) {
                    return $node->attr("title");
                })
        );
    }

    /** @return Request[] */
    protected function initialRequests(): array
    {
        $keywords = Keyword::orderBy("id", "desc")
            ->whereNull("scraped_at")
            ->get();
        $requests = [];

        foreach ($keywords as $keyword) {
            $keyword->scraped_at = Carbon::now();
            $keyword->save();

            $requests[] = new Request(
                "GET",
                self::buildUrl($keyword),
                [$this, "parse"],
                $this->getOptions($keyword)
            );
        }

        return $requests;
    }

    public function parse(Response $response): \Generator
    {
        $this->count++;

        $keyword = $response->getRequest()->getOptions()["keyword"];

        $this->saveImages($response, $keyword);
        $post = Post::regenerate($keyword);
        yield from [];
    }

    public static function buildUrl(Keyword $keyword): string
    {
        return "https://www.bing.com/images/async?" .
            http_build_query(["q" => $keyword->name]);
    }

    public function saveImages(Response $response, $keyword)
    {
        $images = collect($this->scrapeImages($response))
            ->map(function ($item) use ($keyword) {
                $item["keyword_id"] = $keyword->id;

                return $item;
            })
            ->reject(function ($item) {
                return Badwords::isDirty(
                    $item["title"] || Badwords::isDirty($item["desc"])
                );
            })
            ->take(config("shuriken.image_count"));

        Image::insert($images->toArray());
    }

    private function getOptions(mixed $keyword)
    {
        $options = [
            "keyword" => $keyword,
            "count" => $this->count,
        ];

        if (config("shuriken.proxy")) {
            $options["proxy"] = config("shuriken.proxy");
        }

        return $options;
    }
}