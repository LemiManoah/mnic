@extends('pdf.layout')

@section('title', $clubName . ' - Club profile')
@section('doc-type', 'Club profile')

@section('report-styles')
    body { line-height: 1.55; color: #202a2b; }
    .cover { margin-top: 70pt; text-align: center; }
    .cover h1 { font-size: 30pt; line-height: 1.15; margin: 0 0 20pt; color: #153f37; }
    .cover .tagline { font-size: 17pt; font-weight: bold; line-height: 1.5; }
    .cover .established { margin-top: 30pt; color: #526461; letter-spacing: .08em; }
    .cover .motto { margin-top: 40pt; color: #153f37; font-size: 13pt; font-weight: bold; text-transform: uppercase; }
    .profile-section { page-break-before: always; }
    .eyebrow { color: #75807e; font-size: 8pt; text-transform: uppercase; letter-spacing: .12em; }
    .profile-section h1 { color: #153f37; font-size: 22pt; margin: 4pt 0 18pt; line-height: 1.2; }
    .profile-section h2 { color: #153f37; font-size: 12pt; }
    .profile-section p { margin: 0 0 10pt; white-space: pre-line; }
    .profile-card { margin: 8pt 0; padding: 8pt 10pt; border-left: 3pt solid #a88035; background: #f5f6f2; page-break-inside: avoid; }
    .profile-card strong { display: block; color: #153f37; margin-bottom: 2pt; }
    .priority { margin-top: 16pt; padding: 12pt; background: #edf2ee; border: 1px solid #d6e0d9; }
    .closing { margin-top: 28pt; text-align: center; font-weight: bold; font-size: 15pt; color: #153f37; }
@endsection

@section('content')
    <div class="cover">
        <p class="eyebrow">Club profile</p>
        <h1>{{ $clubName }}</h1>
        <p class="tagline">{{ $profile['cover']['tagline'] }}</p>
        <p class="established">ESTABLISHED {{ $profile['cover']['established'] }}</p>
        <p class="motto">{{ $profile['cover']['motto'] }}</p>
    </div>

    <section class="profile-section">
        <p class="eyebrow">Our identity</p>
        <h1>{{ $profile['identity']['heading'] }}</h1>
        <p>{{ $profile['identity']['body'] }}</p>
        @foreach (explode("\n", $profile['identity']['principles']) as $item)
            @php([$title, $description] = array_pad(explode('|', $item, 2), 2, ''))
            <div class="profile-card"><strong>{{ $title }}</strong>{{ $description }}</div>
        @endforeach
    </section>

    <section class="profile-section">
        <p class="eyebrow">Vision and mission</p>
        <h1>{{ $profile['purpose']['heading'] }}</h1>
        <h2>Our vision</h2>
        <p>{{ $profile['purpose']['vision'] }}</p>
        <h2>Our mission</h2>
        <p>{{ $profile['purpose']['mission'] }}</p>
        <h2>Our promise</h2>
        @foreach (explode("\n", $profile['purpose']['promise']) as $item)
            <div class="profile-card">{{ $item }}</div>
        @endforeach
    </section>

    <section class="profile-section">
        <p class="eyebrow">Strategic pillars</p>
        <h1>{{ $profile['pillars']['heading'] }}</h1>
        <p>These pillars connect the club's financial ambition with the development of its members and the strength of its institution.</p>
        @foreach (explode("\n", $profile['pillars']['items']) as $item)
            @php([$title, $description] = array_pad(explode('|', $item, 2), 2, ''))
            <div class="profile-card"><strong>{{ $title }}</strong>{{ $description }}</div>
        @endforeach
    </section>

    <section class="profile-section">
        <p class="eyebrow">How we work</p>
        <h1>{{ $profile['operating_model']['heading'] }}</h1>
        <p>{{ $profile['operating_model']['intro'] }}</p>
        @foreach (explode("\n", $profile['operating_model']['steps']) as $item)
            @php([$title, $description] = array_pad(explode('|', $item, 2), 2, ''))
            <div class="profile-card"><strong>{{ $title }}</strong>{{ $description }}</div>
        @endforeach
    </section>

    <section class="profile-section">
        <p class="eyebrow">Investment philosophy</p>
        <h1>{{ $profile['investment']['heading'] }}</h1>
        <p>{{ $profile['investment']['intro'] }}</p>
        <h2>What we look for</h2>
        @foreach (explode("\n", $profile['investment']['look_for']) as $item)
            <div class="profile-card">{{ $item }}</div>
        @endforeach
        <h2>What we avoid</h2>
        @foreach (explode("\n", $profile['investment']['avoid']) as $item)
            <div class="profile-card">{{ $item }}</div>
        @endforeach
        <div class="priority"><strong>Our current investment priority</strong><p>{{ $profile['investment']['priority'] }}</p></div>
    </section>

    <section class="profile-section">
        <p class="eyebrow">Governance</p>
        <h1>{{ $profile['governance']['heading'] }}</h1>
        <p>{{ $profile['governance']['intro'] }}</p>
        @foreach (explode("\n", $profile['governance']['principles']) as $item)
            @php([$title, $description] = array_pad(explode('|', $item, 2), 2, ''))
            <div class="profile-card"><strong>{{ $title }}</strong>{{ $description }}</div>
        @endforeach
        <h2>Accountability in practice</h2>
        @foreach (explode("\n", $profile['governance']['accountability']) as $item)
            <div class="profile-card">{{ $item }}</div>
        @endforeach
    </section>

    <section class="profile-section">
        <p class="eyebrow">Membership and future</p>
        <h1>{{ $profile['membership']['heading'] }}</h1>
        <h2>What membership requires</h2>
        @foreach (explode("\n", $profile['membership']['requirements']) as $item)
            <div class="profile-card">{{ $item }}</div>
        @endforeach
        <h2>Our long-term ambition</h2>
        @foreach (explode("\n", $profile['membership']['ambitions']) as $item)
            <div class="profile-card">{{ $item }}</div>
        @endforeach
        <p>{{ $profile['membership']['enquiries'] }}</p>
        <p class="closing">{{ $profile['membership']['closing'] }}</p>
    </section>
@endsection
