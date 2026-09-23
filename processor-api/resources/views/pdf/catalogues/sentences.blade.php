@extends('pdf.catalogues.layout')

@section('title', $catalogue['title'])

@section('body')
    @php($frontendUrl = rtrim($frontend_url, '/'))
    <header class="header">
        <p class="meta">{{ $catalogue['type_label'] }} catalogue / {{ $catalogue['author'] }} /
            {{ $catalogue['date']->format('Y-m-d') }}</p>
        <h1>{{ $catalogue['title'] }}</h1>
        <div class="links">
            <a href="{{ $frontendUrl . '/catalogues/' . $catalogue['uuid'] }}">Read catalogue online</a>
            <a href="{{ $frontendUrl . '/users/' . $catalogue['user_id'] }}">Author profile</a>
        </div>
    </header>

    <section>
        <h2>Saved Sentences</h2>
        <table>
            <thead>
                <tr>
                    <th>Sentence</th>
                    <th>Tatoeba</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sentences as $sentence)
                    <tr>
                        <td class="japanese"><a
                                href="{{ $frontendUrl . '/sentence/' . $sentence['id'] }}">{{ $sentence['content'] }}</a>
                        </td>
                        <td>
                            @if (!empty($sentence['tatoeba_entry']))
                                <a
                                    href="{{ 'https://tatoeba.org/eng/sentences/show/' . $sentence['tatoeba_entry'] }}">{{ $sentence['tatoeba_entry'] }}</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <footer class="footer">
        <p>Created using JPLearning. Visit <a href="{{ $frontendUrl }}">JPLearning</a> for more learning opportunities.</p>
    </footer>
@endsection
