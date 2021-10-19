@extends('dev/index')

@section('content')

    <script>
        $.ajax('/api/public/login', {type: 'post'});
    </script>


@endsection