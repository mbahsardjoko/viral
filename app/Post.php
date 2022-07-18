<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        "published_at" => "datetime",
        "tags" => "array",
    ];

    const TEMPLATES = [
        "category",
        "title",
        "json_ld",
        "content",
        "markdown",
        "tags",
    ];

    public static function regenerate(Keyword $keyword): Post
    {
        $post = Post::firstOrCreate(["keyword_id" => $keyword->id]);

        $post->setPublicationDate();

        foreach (self::TEMPLATES as $template) {
            self::generate($template, $post);
            $post->refresh();
            self::spin($template, $post);
            $post->refresh();
        }

        $post->slug = str($post->id . "-" . $keyword->name)->slug();

        $post->category = config("export.category");
        $post->save();

        return $post;
    }

    public static function spin($template, $post)
    {
        $post->{$template} = Helper::spintax($post->{$template});
        $post->save();
        return $post;
    }

    public function setPublicationDate()
    {
        $this->published_at = Carbon::now();
        $this->save();

        return $this;
    }

    public static function generate(string $template, Post $post)
    {
        if ($template === "tags") {
            $tags = [];

            foreach ($post->keyword->related_keywords as $related_keyword) {
                $tags[] = trim(
                    view()
                        ->make("tags", ["keyword" => $related_keyword])
                        ->render()
                );
            }

            $post->tags = $tags;
            $post->save();
            return $post;
        }

        $content = view()
            ->make($template, [
                "post" => $post,
                "keyword" => $post->keyword,
                "images" => $post->keyword->images,
            ])
            ->render();

        $post->{$template} = trim($content);
        $post->save();
        return $post;
    }

    public function keyword()
    {
        return $this->belongsTo(Keyword::class);
    }

    public function getPreviousAttribute()
    {
        return Post::where("id", "<", $this->id)
            ->orderBy("id", "desc")
            ->first();
    }

    public function getParentAttribute()
    {
        $parent_keyword = $this->keyword->parent;
        if (is_null($parent_keyword)) {
            return null;
        }

        return Post::where("keyword_id", $parent_keyword->id)->first();
    }

    public function getNextAttribute()
    {
        return Post::where("id", ">", $this->id)
            ->orderBy("id", "asc")
            ->whereNotNull("content")
            ->first();
    }

    public function getRelatedAttribute()
    {
        return Post::where("keyword_id", $this->keyword->id)
            ->with("keyword")
            ->get();
    }

    public static function cachePosts($posts)
    {
        foreach ($posts as $post) {
            cache()->get("posts|" . $post->slug, function () use ($post) {
                return $post;
            });
        }
    }
}