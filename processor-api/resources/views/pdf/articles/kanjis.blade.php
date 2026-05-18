@extends('pdf.articles.layout')

@section('title', $article['title_jp'])

@section('body')
    @php($frontendUrl = rtrim($frontend_url, '/'))
    <header class="header">
        <p class="meta">{{ $article['author'] }} / {{ $article['date']->format('Y-m-d') }}</p>
        <h1>{{ $article['title_jp'] }}</h1>
        @if($article['title_en'])
            <p>{{ $article['title_en'] }}</p>
        @endif
        <div class="links">
            <a href="{{ $frontendUrl.'/articles/'.$article['uuid'] }}">Read article online</a>
            <a href="{{ $article['source_link'] }}">Original source</a>
        </div>
    </header>

    <section class="content">
        <p>{{ $article['content_jp'] }}</p>
    </section>

    <section>
        <h2>Found Kanjis</h2>
        <table>
            <thead>
                <tr>
                    <th>Kanji</th>
                    <th>Onyomi</th>
                    <th>Kunyomi</th>
                    <th>Meaning</th>
                    <th>JLPT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kanjis as $kanji)
                    <tr>
                        <td class="japanese"><a href="{{ $frontendUrl.'/kanjis/'.$kanji['id'] }}">{{ $kanji['kanji'] }}</a></td>
                        <td>{{ $kanji['onyomi'] }}</td>
                        <td>{{ $kanji['kunyomi'] }}</td>
                        <td>{{ $kanji['meaning'] }}</td>
                        <td>{{ $kanji['jlpt'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <footer class="footer">
        <p>Created using JPLearning. Visit <a href="{{ $frontendUrl }}">JPLearning</a> for more learning opportunities.</p>
    </footer>
@endsection
