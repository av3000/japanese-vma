@extends('pdf.kanjis.mainlayout')

@section('title')
{{ $title }}
@endsection

@section("links")
    <div class="col-md-6">
        <a class="text-primary float-left" href="{{ 'localhost:3000/list/'.$list_id) }}">Read List online</a>
    </div>
    <div class="col-md-6">
        <!-- <a class="text-primary float-right" href="{{ url('user/'.$user_id) }}">Author profile</a> -->
    </div>
@endsection

@section('content')
<div class="col-lg-6 col-md-12 mb-5">
    <div class="card">
        <div class="card-header card-header-warning">
            <p class="card-category">Radicals list | {{$title}} by 
             {{ $author }}</p>
        </div>
        <div class="card-body table-responsive kanjis-table">
            <table class="table table-hover">
            <thead class="text-warning">
                <tr>
                <th>Radical</th>
                <th>Hiragana</th>
                <th>Strokes</th>
                <th>Meaning</th>
                </tr>
            </thead>
            <tbody>
                @foreach($radicalList as $singleRadical)
                    <tr>
                    <!-- <td lang="ja" id="kanjiTd"> <a href="{{ url('radical/'. $singleRadical->id)}}" target="_blank"> {{ $singleRadical->radical }} </a></td> -->
                    <td lang="ja" id="kanjiTd"> {{ $singleRadical->radical }} </td>
                    <td lang="ja"> {{ $singleRadical->hiragana }} </td>
                    <td> {{ $singleRadical->strokes }} </td>
                    <td> {{ $singleRadical->meaning }} </td>
                    </tr>
                @endforeach
            </tbody>
            </table>
        </div>
    </div>
</div>
@endsection