@extends('layouts.app')

@section('content')
<div class="container">
    <h1>إضافة معهد جديد</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('institutes.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>اسم المعهد</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">حفظ</button>
        <a href="{{ route('institutes.index') }}" class="btn btn-secondary">رجوع</a>
    </form>
</div>
@endsection
