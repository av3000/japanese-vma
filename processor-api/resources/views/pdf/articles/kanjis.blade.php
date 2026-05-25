@extends('pdf.articles.layout')

@section('title', $article['title_jp'])

@section('body')
    @php($frontendUrl = rtrim($frontend_url, '/'))
    <header class="header">
        <p class="meta">{{ $article['author'] }} / {{ $article['date']->format('Y-m-d') }}</p>
        <h1>{{ $article['title_jp'] }}</h1>
        @if ($article['title_en'])
            <p>{{ $article['title_en'] }}</p>
        @endif
        <div class="links">
            <a href="{{ $frontendUrl . '/articles/' . $article['uuid'] }}">Read article online</a>
            <a href="{{ $article['source_link'] }}">Original source</a>
        </div>
    </header>

    <section class="content">
        <p>{{ $article['content_jp'] }}</p>
    </section>

    <section>
        <h2>Found Kanjis</h2>
        <table class="kanjis-table">
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
                @foreach ($kanjis as $kanji)
                    <tr>
                        <td class="japanese"><a href="{{ $frontendUrl . '/kanji/' . $kanji['id'] }}">{{ $kanji['kanji'] }}</a>
                        </td>
                        <td>
                            @foreach ($kanji['onyomi'] as $onyomi)
                                <span class="value-line">{{ $onyomi }}</span>
                            @endforeach
                        </td>
                        <td>
                            @foreach ($kanji['kunyomi'] as $kunyomi)
                                <span class="value-line">{{ $kunyomi }}</span>
                            @endforeach
                        </td>
                        <td>
                            @foreach ($kanji['meaning'] as $meaning)
                                <span class="value-line">{{ $meaning }}</span>
                            @endforeach
                        </td>
                        <td>{{ $kanji['jlpt'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <footer class="footer">
        <p>Created using JPLearning. Visit <a href="{{ $frontendUrl }}">JPLearning</a> for more learning opportunities.
        </p>
    </footer>
@endsection
