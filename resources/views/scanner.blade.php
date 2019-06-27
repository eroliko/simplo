@extends('layout')

@section('title')
    URL scanner
@endsection

@section('content')
    <div class="col">
        <h1>URL scanner</h1>
        <form method="POST" action="/scanner">
            @csrf
            <div class="form-group">
                <label for="url">URL</label>
                <input type="text" name="url" class="form-control" id="url" placeholder="Write URL here" value="{{old('url')}}" required>
            </div>
            <button type="submit" class="btn btn-primary">Scan URL</button>
        </form>
        @include('errors')
    </div>
@endsection
