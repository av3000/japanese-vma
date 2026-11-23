@extends('pdf.catalogues.layout')

@section('title', $catalogue['title'])

@section('body')
    @php($frontendUrl = rtrim($frontend_url, '/'))
    <header class="header">
        <p class="meta">{{ $catalogue['type_label'] }} catalogue / {{ $catalogue['author'] }} / {{ $catalogue['date']->format('Y-m-d') }}</p>
        <h1>{{ $catalogue['title'] }}</h1>
        <div class="links">
            <a href="{{ $frontendUrl.'/catalogues/'.$catalogue['uuid'] }}">Read catalogue online</a>
            <a href="{{ $frontendUrl.'/users/'.$catalogue['user_id'] }}">Author profile</a>
        </div>
    </header>

    <section>
        <h2>Saved Words</h2>
        <table>
            <thead>
                <tr>
                    <th>Word</th>
                    <th>Furigana</th>
                    <th>Meaning</th>
                    <th>JLPT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($words as $word)
                    <tr>
                        <td class="japanese"><a href="{{ $frontendUrl.'/words/'.$word['id'] }}">{{ $word['word'] }}</a></td>
                        <td>{{ $word['furigana'] }}</td>
                        <td>{{ $word['meaning'] }}</td>
                        <td>{{ $word['jlpt'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <footer class="footer">
        <p>Created using JPLearning. Visit <a href="{{ $frontendUrl }}">JPLearning</a> for more learning opportunities.</p>
    </footer>
@endsection
