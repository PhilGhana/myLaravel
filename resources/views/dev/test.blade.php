@extends('dev/index')

@section('content')

    <script>
        let data = {
            arr: [1, 2, 3, 4]
        };
        $.ajax('/test', {type: 'post', data});


    </script>


@endsection