@extends('layout')

@section('head')
	<title>{{ $image['title'] }}</title>
@endsection

@section('bg')
	{{ $image['url'] }}
@endsection

@section('header')
	<div class="posts-single">
		<h1>{{ $image['title'] }}</h1>

		@php
			shuffle($sentences);
		@endphp

		<div class="navi text-left">
			@if(!empty($sentences))
				<p>
					{{ @array_pop($sentences) }} <br>
					<img src="{{ $image['url'] }}" style="margin-bottom: 8px;" width="100%">
				</p>

				<div class="panel-heading">
					<h3 class="panel-title">Description</h3>
				</div>
				<div class="panel-body">
					<p><strong>{{ $image['title'] }}</strong> is free <strong>image</strong> that you can download for free in {{ config('site.name') }}.
						This {{ $image['title'] }} has {{ $image['width']}}px x {{ $image['height']}}px resolution. {{ @array_pop($sentences) }}
						Image type is {{ $image['filetype'] }}.
					</p>
				</div>

				<div class="panel-heading">
					<h3 class="panel-title">Detail File</h3>
				</div>
				<div class="panel-body">
					<p>Title: {{ $image['title'] }} <br>
						Resolution: {{ $image['width'] }}px x {{ $image['height'] }}px <br>
						File Size: {{ $image['size'] }} <br>
					</p>
					<p>
						<a href="{{ $image['url'] }}" style="background: blue; color: white; padding: 10px;">Download Image</a>
					</p>
				</div>
				<p>{{ @array_pop($sentences) }} {{ @array_pop($sentences) }} <br>
					@foreach($images as $image)
						<a class="badge badge-{{ collect(['primary', 'secondary', 'success', 'info', 'danger', 'warning', 'light', 'dark'])->random() }}" href="{{ preview_url($image, $post) }}">{{ $image['title'] }}</a>
					@endforeach
				</p>
			@endif
		</div>
	</div>

@endsection

@section('content')
@endsection