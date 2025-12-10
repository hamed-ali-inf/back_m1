@extends('layouts.app')

@section('content')
<div class="container">
    <h1>تعديل المعهد</h1>

    <form action="{{ route('institutes.update', $institute->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label>اسم المعهد</label>
            <input type="text" name="name" class="form-control" value="{{ $institute->name }}" required>
        </div>
        <button type="submit" class="btn btn-success">تحديث</button>
        <a href="{{ route('institutes.index') }}" class="btn btn-secondary">رجوع</a>
    </form>
</div>
@endsection
