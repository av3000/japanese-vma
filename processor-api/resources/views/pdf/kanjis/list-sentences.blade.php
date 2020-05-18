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
            <p class="card-category">Sentences list | {{ $title }} by 
            <a class="text-primary float-right" href="{{ url('user/'.$user_id) }}"> {{ $author }} </a></p>
        </div>
        <div class="card-body table-responsive kanjis-table">
            <table class="table table-hover">
            <thead class="text-warning">
                <tr>
                <th>Sentence</th>
                <th>Tatoeba Link</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sentenceList as $singleSentence)
                    <tr>
                    <td lang="ja" id="kanjiTd"> {{ $singleSentence->content }} </td>
                    <td class="adjust-to-kanji"> <a href="{{ url('https://tatoeba.org/eng/sentences/show/'. $singleSentence->tatoeba_entry) }}"> {{ $singleSentence->tatoeba_entry }} </a> </td>
                    <!-- <td class="adjust-to-kanji"> {{ $singleSentence->tatoeba_entry }} </td> -->
                    </tr>
                @endforeach
            </tbody>
            </table>
        </div>
    </div>
</div>
@endsection