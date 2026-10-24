@props([
    'x',
    'y',
    'hair' => 'short',
    'hairColor' => '#d9d4cc',
    'clothes' => '#8fb3de',
    'glasses' => false,
    'pose' => 'none',
])

{{--
    イラスト用の笑顔の高齢者1人分。(x, y) は顔の中心。
    hair: bun / short / curly / bald / cap、pose: none / clap（拍手）/ wave（手を振る）
--}}
@php
    $line = '#46342a';
    $skin = '#f6d3b3';
    $cheek = '#f7a8a0';
    $mouth = '#b94a14';
@endphp

<g>
    @if ($pose === 'wave')
        <path d="M{{ $x + 28 }} {{ $y + 46 }} L{{ $x + 40 }} {{ $y + 10 }}" stroke="{{ $clothes }}" stroke-width="10" stroke-linecap="round" />
    @endif

    <path
        d="M{{ $x - 40 }} {{ $y + 84 }} Q{{ $x - 40 }} {{ $y + 32 }} {{ $x }} {{ $y + 30 }} Q{{ $x + 40 }} {{ $y + 32 }} {{ $x + 40 }} {{ $y + 84 }} Z"
        fill="{{ $clothes }}"
        stroke="{{ $line }}"
        stroke-width="3"
    />
    <circle cx="{{ $x }}" cy="{{ $y }}" r="28" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="3" />

    @switch($hair)
        @case('bun')
            <circle cx="{{ $x }}" cy="{{ $y - 36 }}" r="11" fill="{{ $hairColor }}" stroke="{{ $line }}" stroke-width="3" />
            <path
                d="M{{ $x - 29 }} {{ $y + 2 }} Q{{ $x - 31 }} {{ $y - 32 }} {{ $x }} {{ $y - 31 }} Q{{ $x + 31 }} {{ $y - 32 }} {{ $x + 29 }} {{ $y + 2 }} Q{{ $x + 20 }} {{ $y - 14 }} {{ $x }} {{ $y - 16 }} Q{{ $x - 20 }} {{ $y - 14 }} {{ $x - 29 }} {{ $y + 2 }} Z"
                fill="{{ $hairColor }}"
                stroke="{{ $line }}"
                stroke-width="3"
                stroke-linejoin="round"
            />
            @break
        @case('short')
            <path
                d="M{{ $x - 29 }} {{ $y + 2 }} Q{{ $x - 31 }} {{ $y - 32 }} {{ $x }} {{ $y - 31 }} Q{{ $x + 31 }} {{ $y - 32 }} {{ $x + 29 }} {{ $y + 2 }} Q{{ $x + 20 }} {{ $y - 14 }} {{ $x }} {{ $y - 16 }} Q{{ $x - 20 }} {{ $y - 14 }} {{ $x - 29 }} {{ $y + 2 }} Z"
                fill="{{ $hairColor }}"
                stroke="{{ $line }}"
                stroke-width="3"
                stroke-linejoin="round"
            />
            @break
        @case('curly')
            @foreach ([[-22, -14, 10], [-12, -25, 10], [0, -29, 10], [12, -25, 10], [22, -14, 10], [-28, 0, 8], [28, 0, 8]] as [$dx, $dy, $r])
                <circle cx="{{ $x + $dx }}" cy="{{ $y + $dy }}" r="{{ $r }}" fill="{{ $hairColor }}" stroke="{{ $line }}" stroke-width="2.5" />
            @endforeach
            @break
        @case('bald')
            <ellipse cx="{{ $x - 28 }}" cy="{{ $y + 2 }}" rx="7" ry="10" fill="{{ $hairColor }}" stroke="{{ $line }}" stroke-width="2.5" />
            <ellipse cx="{{ $x + 28 }}" cy="{{ $y + 2 }}" rx="7" ry="10" fill="{{ $hairColor }}" stroke="{{ $line }}" stroke-width="2.5" />
            <path d="M{{ $x - 9 }} {{ $y - 19 }} q9 -6 18 0" fill="none" stroke="#fffdf7" stroke-width="3" stroke-linecap="round" />
            @break
        @case('cap')
            <path
                d="M{{ $x - 29 }} {{ $y - 14 }} Q{{ $x - 27 }} {{ $y - 40 }} {{ $x }} {{ $y - 39 }} Q{{ $x + 27 }} {{ $y - 40 }} {{ $x + 29 }} {{ $y - 14 }} Z"
                fill="{{ $hairColor }}"
                stroke="{{ $line }}"
                stroke-width="3"
                stroke-linejoin="round"
            />
            <ellipse cx="{{ $x + 6 }}" cy="{{ $y - 14 }}" rx="30" ry="5" fill="{{ $hairColor }}" stroke="{{ $line }}" stroke-width="3" />
            @break
    @endswitch

    {{-- 笑って細くなった目と、目じりのしわ --}}
    <path
        d="M{{ $x - 14 }} {{ $y }} q5 -6 10 0 M{{ $x + 4 }} {{ $y }} q5 -6 10 0"
        fill="none"
        stroke="{{ $line }}"
        stroke-width="3"
        stroke-linecap="round"
    />
    <path
        d="M{{ $x - 21 }} {{ $y - 3 }} l-4 -3 M{{ $x + 21 }} {{ $y - 3 }} l4 -3"
        stroke="{{ $line }}"
        stroke-width="2"
        stroke-linecap="round"
    />

    @if ($glasses)
        <circle cx="{{ $x - 9 }}" cy="{{ $y - 1 }}" r="8.5" fill="none" stroke="{{ $line }}" stroke-width="2.5" />
        <circle cx="{{ $x + 9 }}" cy="{{ $y - 1 }}" r="8.5" fill="none" stroke="{{ $line }}" stroke-width="2.5" />
    @endif

    <ellipse cx="{{ $x - 18 }}" cy="{{ $y + 9 }}" rx="6" ry="4" fill="{{ $cheek }}" opacity="0.8" />
    <ellipse cx="{{ $x + 18 }}" cy="{{ $y + 9 }}" rx="6" ry="4" fill="{{ $cheek }}" opacity="0.8" />
    <path
        d="M{{ $x - 10 }} {{ $y + 10 }} Q{{ $x }} {{ $y + 24 }} {{ $x + 10 }} {{ $y + 10 }} Z"
        fill="{{ $mouth }}"
        stroke="{{ $line }}"
        stroke-width="2.5"
        stroke-linejoin="round"
    />

    @if ($pose === 'clap')
        <circle cx="{{ $x - 7 }}" cy="{{ $y + 52 }}" r="7" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="2.5" />
        <circle cx="{{ $x + 7 }}" cy="{{ $y + 48 }}" r="7" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="2.5" />
        <path
            d="M{{ $x - 19 }} {{ $y + 40 }} l-6 -5 M{{ $x + 20 }} {{ $y + 36 }} l6 -6"
            stroke="{{ $line }}"
            stroke-width="2.5"
            stroke-linecap="round"
        />
    @endif

    @if ($pose === 'wave')
        <circle cx="{{ $x + 41 }}" cy="{{ $y + 5 }}" r="7" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="2.5" />
        <path
            d="M{{ $x + 52 }} {{ $y - 6 }} q7 6 0 13 M{{ $x + 58 }} {{ $y - 12 }} q11 11 0 24"
            fill="none"
            stroke="{{ $line }}"
            stroke-width="2.5"
            stroke-linecap="round"
        />
    @endif
</g>
