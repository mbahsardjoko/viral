<?php

namespace App;

use Buchin\Badwords\Badwords;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Buchin\GoogleSuggest\GoogleSuggest;
use League\Csv\Reader;

class Keyword extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function txt()
    {
        $csv = Reader::createFromPath(base_path("keywords.txt"));

        $records = $csv->getRecords();

        $keywords = [];
        foreach ($records as $record) {
            $keywords[] = $record[0];
        }

        self::importArray($keywords);
    }

    public static function trends(string $country)
    {
        $keywords = json_decode(
            file_get_contents(
                "http://tools.dojo.cc/api/trends/" . $country . "/keywords"
            ),
            true
        );

        self::importArray($keywords);
    }

    public static function manual()
    {
        $keywords = explode(",", config("shuriken.keywords"));

        self::importArray($keywords);
    }

    /**
     * @param array $keywords
     * @return void
     */
    public static function importArray(array $keywords, $keyword = null): void
    {
        $keywords = collect($keywords)
            ->map(function ($item) use ($keyword) {
                $record = [];
                $record["name"] = $item;
                $record["keyword_id"] = is_null($keyword) ? null : $keyword->id;

                return $record;
            })
            ->reject(function ($item) {
                return Badwords::isDirty($item["name"]);
            });

        foreach ($keywords as $keyword) {
            Keyword::firstOrCreate(["name" => $keyword["name"]], $keyword);
        }
    }

    /**
     * @return void
     */
    protected function import(): void
    {
        if (config("shuriken.keywords_txt")) {
            Keyword::txt();
        }

        if (config("shuriken.trends")) {
            Keyword::trends(config("shuriken.trends"));
        }

        if (config("shuriken.keywords")) {
            Keyword::manual();
        }
    }

    public function suggest()
    {
        return backoff(function () {
            return GoogleSuggest::grab(
                $this->name,
                config("shuriken.language"),
                config("shuriken.country"),
                "i",
                config("shuriken.proxy")
            );
        });
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function getSentencesAttribute()
    {
        return is_array($this->sentences(100, true))
            ? $this->sentences(100, true)
            : $this->sentences(100, true)->toArray();
    }

    public function sentences($count = null, bool $as_collection = false)
    {
        if (!$this->images()->exists()) {
            return [];
        }

        $images = $this->images()
            ->whereNotNull("title")
            ->whereNotNull("desc")
            ->get();

        $sentences = $images
            ->pluck("desc")
            ->merge($images->pluck("title"))
            ->map(function ($item) {
                return str($item)
                    ->before(" | ")
                    ->before(" - ")
                    ->lower()
                    ->ucfirst();
            })
            ->shuffle()
            ->reject(function ($item) {
                return str($item)->wordCount() < 2;
            });

        if (is_null($count)) {
            $count = $sentences->count();
        }

        if ($as_collection) {
            return $sentences->take($count);
        }
        return $sentences->take($count)->implode(". ");
    }

    public function related_keywords()
    {
        return $this->hasMany(Keyword::class);
    }

    public function parent()
    {
        return $this->belongsTo(Keyword::class);
    }
}