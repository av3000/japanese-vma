@extends('pdf.kanjis.mainlayout')

@section('title')
{{ $title }}
@endsection

@section("links")
    <div class="col-md-6">
        <a class="text-primary float-left" href="{{ url('list/'.$list_id) }}">www.jplearning.online/list/{{$list_id}}</a>
    </div>
    <div class="col-md-6">
        <!-- <a class="text-primary float-right" href="{{ url('user/'.$user_id) }}">Author profile</a> -->
    </div>
@endsection

@section('content')
<div class="col-lg-6 col-md-12 mb-5">
    <div class="card">
        <div class="card-header card-header-warning">
            <p class="card-category">Words list | {{ $title }} by 
            <a class="text-primary float-right" href="{{ url('user/'.$user_id) }}"> {{ $author }} </a></p>
        </div>
        <div class="card-body table-responsive kanjis-table">
            <table class="table table-hover">
            <thead class="text-warning">
                <tr>
                <th>Word</th>
                <th>Furigana</th>
                <th>Meaning</th>
                <th>JLPT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($wordList as $singleWord)
                    <tr>
                    <!-- <td lang="ja" id="kanjiTd"> <a href="{{ url('word/'. $singleWord->id)}}" target="_blank"> {{ $singleWord->word }} </a></td> -->
                    <td lang="ja" id="kanjiTd">  {{ $singleWord->word }} </td>
                    <td class="adjust-to-kanji"> {{ $singleWord->furigana }} </td>
                    <td class="adjust-to-kanji"> {{ $singleWord->meaning }} </td>
                    <td class="adjust-to-kanji"> {{ $singleWord->jlpt }} </td>
                    </tr>
                @endforeach
            </tbody>
            </table>
        </div>
    </div>
</div>
@endsection