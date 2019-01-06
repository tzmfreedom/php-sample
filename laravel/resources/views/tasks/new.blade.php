@extends('layouts.app')
@section('content')
    <form action="{{ url('tasks') }}" method="POST" >
        @csrf
        <table>
            <tr>
                <th>Title</th>
                <td><input type="text" name="title"/></td>
            </tr>
            <tr>
                <th>Body</th>
                <td><textarea name="body"></textarea></td>
            </tr>
        </table>
        <input type="submit" value="タスクを追加する"/>
    </form>
@endsection
