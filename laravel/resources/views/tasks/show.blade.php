@extends('layouts.app')

@section('content')
    <table>
        <tr>
            <th>Id</th>
            <td>{{ $task->id }}</td>
        </tr>
        <tr>
            <th>Title</th>
            <td>{{ $task->title }}</td>
        </tr>
        <tr>
            <th>Body</th>
            <td>{{ $task->body }}</td>
        </tr>
    </table>
@endsection
