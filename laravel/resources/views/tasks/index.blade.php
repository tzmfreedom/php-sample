@extends('layouts.app')

@section('content')
    <table>
        <tr>
            <th>Id</th>
            <th>Title</th>
            <th>Body</th>
        </tr>
        @foreach ($tasks as $task)
            <tr>
                <td>{{ $task->id }}</td>
                <td>{{ $task->title }}</td>
                <td>{{ $task->body }}</td>
            </tr>
        @endforeach
    </table>
@endsection
