@extends('dev/index')

@section('content')

    <form action="/upload" method="post" enctype="multipart/form-data">

        <input type="file" name="image">
        <button>submit</button>
    </form>
@endsection