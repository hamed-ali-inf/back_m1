@extends('layouts.app')

@section('content')
<div class="container">
    <h1>المعاهد</h1>
    <a href="{{ route('institutes.create') }}" class="btn btn-primary mb-3">إضافة معهد جديد</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <tr>
            <th>ID</th>
            <th>اسم المعهد</th>
            <th>الإجراءات</th>
        </tr>
        @foreach($institutes as $institute)
        <tr>
            <td>{{ $institute->id }}</td>
            <td>{{ $institute->name }}</td>
            <td>
                <a href="{{ route('institutes.show', $institute->id) }}" class="btn btn-info btn-sm">عرض</a>
                <a href="{{ route('institutes.edit', $institute->id) }}" class="btn btn-warning btn-sm">تعديل</a>
                <form action="{{ route('institutes.destroy', $institute->id) }}" method="POST" style="display:inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد؟')">حذف</button>
                </form>
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection
