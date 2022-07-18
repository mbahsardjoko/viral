@extends('layout')

@section('head')
<title>{{ $post->title }}</title>
@endsection

@section('bg')
{{ collect($images)->random()['image'] }}
@endsection

@section('header')
	<h1>{{ $post->title }}</h1>

	@php
		shuffle($sentences);
	@endphp

	<div class="navi text-center">
		@if(!empty($sentences))
			<p>{{ @array_pop($sentences) }} {{ @array_pop($sentences) }} {{ @array_pop($sentences) }} <br>
				@foreach($related as $r)
					<a class="badge badge-{{ collect(['primary', 'secondary', 'success', 'info', 'danger', 'warning', 'light', 'dark'])->random() }}" href="{{ image_url($r) }}">{{ $r->keyword->name }}</a>
				@endforeach
			</p>
		@endif
	</div>

@endsection

@section('content')
	
	<div class="columns">
		@foreach(collect($images)->shuffle()->chunk(4) as $chunked)
		
			@foreach($chunked as $image)
				<div class="posts-list">
					<a href="{{ preview_url($image, $post) }}" target="_blank">
						<img class="img-fluid" src="{{ $image['url'] }}" alt="{{ $image['title'] }}" onerror="this.onerror=null;this.src='{{ $image['thumbnail'] }}';"></a>
					<p>{{ $image['title'] }}</p>
				</div>
			@endforeach
			 
		@if($loop->iteration == 2)
	</div>
	<div class="posts-lists">
		<div class="text-center">
			<h3>{{ @array_pop($sentences) }}</h3>
			<img src="{{ collect($images)->random()['url'] }}" align="center" style="margin-bottom: 8px;">

			@foreach(collect($sentences)->chunk(4) as $chunked_sentences)
				<p>
					@if($loop->first)
						<strong>{{ $post->title }}</strong>.
					@endif

					@foreach($chunked_sentences as $chunked_sentence)
						{{ $chunked_sentence }}
					@endforeach
				</p>
			@endforeach
		</div>
	</div>
	<div class="columns">
		@endif 

	@endforeach
	</div>
@endsection