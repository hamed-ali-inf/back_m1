@extends('layouts.app')

@section('content')
<div class="container">
    <h1>لوحة تحكم {{ $institute->name }}</h1>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card p-3 bg-primary text-white">
                <h3>الأقسام</h3>
                <p>{{ $departments_count }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 bg-success text-white">
                <h3>الأساتذة</h3>
                <p>{{ $teachers_count }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 bg-warning text-white">
                <h3>الطلبة</h3>
                <p>{{ $students_count }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
