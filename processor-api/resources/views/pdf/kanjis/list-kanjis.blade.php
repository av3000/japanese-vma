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
            <p class="card-category">Kanjis list | {{ $title }} by 
            {{ $author }}</p>
        </div>
        <div class="card-body table-responsive kanjis-table">
            <table class="table table-hover">
            <thead class="text-warning">
                <tr>
                <th>Kanji</th>
                <th>Onyomi</th>
                <th>Kunyomi</th>
                <th>Meaning</th>
                <th>JLPT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kanjiList as $singleKanji)
                    <tr>
                    <!-- <td lang="ja" id="kanjiTd"> <a href="{{ url('kanji/'. $singleKanji->id)}}" target="_blank"> {{ $singleKanji->kanji }} </a></td> -->
                    <td lang="ja" id="kanjiTd"> {{ $singleKanji->kanji }} </td>
                    <td lang="ja"> {{ $singleKanji->onyomi }} </td>
                    <td lang="ja"> {{ $singleKanji->kunyomi }} </td>
                    <td> {{ $singleKanji->meaning }} </td>
                    <td> {{ $singleKanji->jlpt }} </td>
                    </tr>
                @endforeach
            </tbody>
            </table>
        </div>
    </div>
</div>
@endsection