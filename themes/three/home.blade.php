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
	
		@foreach(posts(40)->chunk(5) as $chunked)
				@foreach($chunked as $n => $post)
					@if($n==0 || $n==5 || $n==10 || $n==15 || $n==20 || $n==25 || $n==30 || $n==35||$n==40)
					<div class="posts-image posts-image-big">
						<div class="posts-image-content">
							<h2><a href="{{ image_url($post) }}">
								{{ ucwords($post->title) }}
							</a></h2>
							<a href="{{ image_url($post) }}">
								<img src="{{ image_url($post, true) }}" alt="{{ $post->title }}" class="img-fluid" onerror="this.onerror=null;this.src='{{ image_url($post, true, true) }}';">
							</a> 
						</div>
					</div>
					@else
					<div class="posts-image">
						<div class="posts-image-content">
							<h2><a href="{{ image_url($post) }}">
								{{ $post->title }}
							</a></h2>
							<a href="{{ image_url($post) }}">
								<img src="{{ image_url($post, true) }}" alt="{{ $post->title }}" class="img-fluid" onerror="this.onerror=null;this.src='{{ image_url($post, true, true) }}';">
							</a> 
						</div>
					</div>
					@endif
				@endforeach 
		@endforeach 
		
@endsection