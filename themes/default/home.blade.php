@extends('layout')

@section('content')
<section>
    @foreach($posts as $post)
        <aside>
            @php
            $image = $post->keyword->images->random();
            @endphp
            <a href="{{ $image['image'] }}" target="_blank"><img alt="{{ $image['title'] }}" src="{{ $image['thumbnail'] }}" width="100%" onerror="this.onerror=null;this.src='{{ $image['image'] }}';"></a>
            <h3><a href="{{ $post->slug }}.html">{{ $post->title }}</a></h3>
        </aside>
    @endforeach
</section>
@endsection