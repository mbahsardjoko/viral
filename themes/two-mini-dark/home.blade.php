@extends('layout')

@section('head')
<title>{{ site_name() }}</title>
@endsection

@section('bg')
@endsection

@section('content')
	@if(isset($_GET['img']))
	<div class="row">
		<div class="col-md-12 text-center">
			<p><a href="{{ $_GET['img'] }}" class="btn btn-outline-primary" download="image">Download Image</a></p>
			<p><img src="{{ $_GET['img'] }}" alt="" class="img-fluid"></p>

		</div> 
	</div>
	<div class="mt-3"></div>
	@endif
	<div class="columns">
		@foreach($posts->shuffle()->take(16)->chunk(4) as $chunked) 
				@foreach($chunked as $post)
					<div class="posts-list">
						@php
			            $image = $post->keyword->images->random();
			            @endphp
						<a href="{{ route('image', $post->slug) }}" target="_blank"><img alt="{{ $image['title'] }}" src="{{ $image['thumbnail'] }}" width="100%" onerror="this.onerror=null;this.src='{{ $image['image'] }}';"></a>
						<p class="text-center">{{ $post->title }}</p>
					</div>
				@endforeach 
		@endforeach
	</div>
@endsection