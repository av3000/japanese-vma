@extends('pdf.kanjis.mainlayout')

@section('title')
{{ $title_jp }}
@endsection

@section("links")
    <div class="col-md-6">
        <a class="text-primary float-left" href="{{ 'localhost:3000/article/'.$article_id }}" target="_blank">Read Article online</a>
    </div>
    <div class="col-md-6">
        <a class="text-primary float-right" href="{{ url($source_link) }}">original source</a>
    </div>
@endsection

@section('content')
<div class="col-md-12 mb-5">
    <h3>{{ $title_jp }}</h3>
    <p> {{ $content_jp }} </p>
</div>
<div class="page-break"></div>

<div class="col-lg-6 col-md-12 mb-5">
    <div class="card">
        <div class="card-header card-header-warning">
            <!-- <h4 class="card-title">Employees Stats</h4> -->
            <p class="card-category">Found Kanjis</p>
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
                    <td lang="ja" id="kanjiTd"> <a href="{{ 'localhost:3000/kanji/'. $singleKanji->id}}" target="_blank"> {{ $singleKanji->kanji }} </a></td>
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