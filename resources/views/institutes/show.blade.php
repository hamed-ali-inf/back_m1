@extends('layouts.app')

@section('content')
<div class="container">
    <h1>معهد: {{ $institute->name }}</h1>
    <a href="{{ route('institutes.index') }}" class="btn btn-secondary">رجوع</a>
</div>
@endsection
