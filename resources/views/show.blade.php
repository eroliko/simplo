@extends('layout')

@section('title')
    Scan results
@endsection

<?php
$url = array_pop($data);
$pagespeed = array_pop($data);
?>

@section('content')
    <div class="col">
        <h3>
            <a href="/">Zpět</a>
        </h3>
        <h1>Scan results ({{$url}})</h1>
        <div class="main">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">Service</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $key => $value)
                        <tr>
                            <td>{{$key}}</td>
                            <td>{{$value}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="col">
            <h3>Google Insights</h3>
            <?php
                foreach($pagespeed as $row)
                {
                    if(gettype($row) == "string")
                        echo $row."<br>\n";
                    else
                        var_dump($row);
                }

             ?>
             <div id="footer"></div>
        </div>
    </div>
@endsection
