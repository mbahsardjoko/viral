@extends('layout')

@section('head')
<title>{{ $post->title }}</title>
@endsection

@section('bg')
{{ collect($images)->random()['url'] }}
@endsection

@section('header')
	<h1>{{ $post->title }}</h1>

	@php
		shuffle($sentences);
	@endphp

	<div class="navi text-left">
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
	
	
	@foreach(collect($images)->shuffle()->chunk(4) as $chunked)		
		<div class="clearfix"></div>
			@foreach($chunked as $image)
				<div class="posts-image posts-image-single">
					<div class="posts-image-content">
						<a href="{{ preview_url($image, $post) }}" target="_blank">
							<img class="img-fluid" src="{{ $image['url'] }}" alt="{{ $image['title'] }}" onerror="this.onerror=null;this.src='{{ $image['thumbnail'] }}';"></a>
						<p>{{ $image['title'] }}</p> 
					</div>
				</div>
			@endforeach
			 
		@if($loop->iteration == 2) 
			<div class="posts-images"> 
				<div class="text-left">
					<h3>{{ @array_pop($sentences) }}</h3>
					<p class="text-center"><img src="{{ collect($images)->random()['url'] }}" style="margin-bottom: 8px;"></p>

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
		@endif 

	@endforeach 	
	
@endsection