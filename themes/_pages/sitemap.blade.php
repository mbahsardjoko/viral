{!! '<' . '?' . 'xml version="1.0" encoding="UTF-8"' . '?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('home') }}</loc>
        <lastmod>{{ \Carbon\Carbon::now()->toISOString() }}</lastmod>
    </url>
    @foreach(pages() as $page)
        <url>
            <loc>{{ route('image', ['slug' => $page]) }}</loc>
            <lastmod>{{ \Carbon\Carbon::now()->toISOString() }}</lastmod>
        </url>
    @endforeach
    @foreach($posts as $post)
        <url>
            <loc>{{ route('image', ['slug' => $post->slug]) }}</loc>
            <lastmod>{{ $post->published_at->toISOString() }}</lastmod>
        </url>

        @foreach($post->keyword->images as $image)
            <url>
                <loc>{{ preview_url($image->toArray(), $post) }}</loc>
                <lastmod>{{ $post->published_at->toISOString() }}</lastmod>
            </url>
        @endforeach
    @endforeach
</urlset>