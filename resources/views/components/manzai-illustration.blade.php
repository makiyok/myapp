@php
    /**
     * 観覧する高齢者10人（後ろの列5人、前の列5人）。
     *
     * @var list<array{x: int, y: int, hair: string, hairColor: string, clothes: string, glasses: bool, clap: bool}> $audience
     */
    $audience = [
        ['x' => 160, 'y' => 335, 'hair' => 'bun', 'hairColor' => '#d9d4cc', 'clothes' => '#7cae7a', 'glasses' => false, 'clap' => true],
        ['x' => 280, 'y' => 335, 'hair' => 'bald', 'hairColor' => '#d9d4cc', 'clothes' => '#8fb3de', 'glasses' => true, 'clap' => false],
        ['x' => 400, 'y' => 335, 'hair' => 'curly', 'hairColor' => '#c8c3bd', 'clothes' => '#d98c8c', 'glasses' => false, 'clap' => true],
        ['x' => 520, 'y' => 335, 'hair' => 'cap', 'hairColor' => '#6d7f9e', 'clothes' => '#e9a23b', 'glasses' => false, 'clap' => false],
        ['x' => 640, 'y' => 335, 'hair' => 'short', 'hairColor' => '#f2efea', 'clothes' => '#b59ad6', 'glasses' => true, 'clap' => true],
        ['x' => 90, 'y' => 420, 'hair' => 'curly', 'hairColor' => '#e8e4de', 'clothes' => '#e9a23b', 'glasses' => true, 'clap' => false],
        ['x' => 245, 'y' => 420, 'hair' => 'short', 'hairColor' => '#d9d4cc', 'clothes' => '#6fa8a0', 'glasses' => false, 'clap' => true],
        ['x' => 400, 'y' => 420, 'hair' => 'bald', 'hairColor' => '#e8e4de', 'clothes' => '#c9a27c', 'glasses' => false, 'clap' => true],
        ['x' => 555, 'y' => 420, 'hair' => 'bun', 'hairColor' => '#f2efea', 'clothes' => '#7cae7a', 'glasses' => true, 'clap' => false],
        ['x' => 710, 'y' => 420, 'hair' => 'cap', 'hairColor' => '#8b5e3c', 'clothes' => '#8fb3de', 'glasses' => false, 'clap' => true],
    ];

    $line = '#46342a';
    $skin = '#f6d3b3';
    $cheek = '#f7a8a0';
    $mouth = '#b94a14';
@endphp

<svg
    viewBox="0 0 800 500"
    xmlns="http://www.w3.org/2000/svg"
    role="img"
    aria-labelledby="manzai-illustration-title"
    {{ $attributes }}
>
    <title id="manzai-illustration-title">小学生の漫才コンビと、笑顔で観覧する10人の高齢者のイラスト</title>

    {{-- 壁と床 --}}
    <rect width="800" height="300" fill="#fff6e6" />
    <rect y="300" width="800" height="200" fill="#f8dcb4" />

    {{-- 舞台 --}}
    <rect x="200" y="60" width="400" height="182" fill="#ffecd0" />
    <path d="M180 40 H252 Q214 140 236 242 H180 Z" fill="#d65f24" stroke="{{ $line }}" stroke-width="3" stroke-linejoin="round" />
    <path d="M620 40 H548 Q586 140 564 242 H620 Z" fill="#d65f24" stroke="{{ $line }}" stroke-width="3" stroke-linejoin="round" />
    <rect x="170" y="28" width="460" height="42" rx="12" fill="#b94a14" stroke="{{ $line }}" stroke-width="3" />
    <text x="400" y="57" text-anchor="middle" font-size="20" font-weight="900" fill="#fffdf7">ふれあい演芸会</text>
    <rect x="170" y="240" width="460" height="34" rx="6" fill="#c9a27c" stroke="{{ $line }}" stroke-width="3" />

    {{-- センターマイク --}}
    <path d="M388 240 H412" stroke="{{ $line }}" stroke-width="5" stroke-linecap="round" />
    <path d="M400 240 V188" stroke="#735a47" stroke-width="4" />
    <ellipse cx="400" cy="180" rx="6" ry="9" fill="{{ $line }}" />

    {{-- ボケの子（左）：手を振ってあいさつ --}}
    <path d="M338 214 V237 M352 214 V237" stroke="{{ $line }}" stroke-width="7" stroke-linecap="round" />
    <ellipse cx="336" cy="240" rx="8" ry="4" fill="{{ $line }}" />
    <ellipse cx="354" cy="240" rx="8" ry="4" fill="{{ $line }}" />
    <path d="M328 182 L308 154" stroke="#5b8def" stroke-width="9" stroke-linecap="round" />
    <circle cx="306" cy="149" r="6" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="2.5" />
    <path d="M296 136 q-7 6 0 13 M290 128 q-10 10 0 22" fill="none" stroke="{{ $line }}" stroke-width="2.5" stroke-linecap="round" />
    <path d="M362 184 L370 208" stroke="#5b8def" stroke-width="9" stroke-linecap="round" />
    <circle cx="371" cy="211" r="6" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="2.5" />
    <rect x="325" y="172" width="40" height="46" rx="12" fill="#5b8def" stroke="{{ $line }}" stroke-width="3" />
    <path d="M345 178 l-9 -5 v10 z M345 178 l9 -5 v10 z" fill="#e5484d" />
    <circle cx="345" cy="150" r="22" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="3" />
    <path d="M323 149 Q322 126 345 126 Q368 126 367 149 Q357 136 345 139 Q333 136 323 149 Z" fill="#33261e" />
    <circle cx="337" cy="152" r="2.6" fill="{{ $line }}" />
    <circle cx="353" cy="152" r="2.6" fill="{{ $line }}" />
    <ellipse cx="331" cy="159" rx="5" ry="3.5" fill="{{ $cheek }}" opacity="0.8" />
    <ellipse cx="359" cy="159" rx="5" ry="3.5" fill="{{ $cheek }}" opacity="0.8" />
    <path d="M338 159 Q345 170 352 159 Z" fill="{{ $mouth }}" stroke="{{ $line }}" stroke-width="2" stroke-linejoin="round" />

    {{-- ツッコミの子（右）：手の甲でツッコミ --}}
    <path d="M448 214 V237 M462 214 V237" stroke="{{ $line }}" stroke-width="7" stroke-linecap="round" />
    <ellipse cx="446" cy="240" rx="8" ry="4" fill="{{ $line }}" />
    <ellipse cx="464" cy="240" rx="8" ry="4" fill="{{ $line }}" />
    <path d="M438 184 L414 168" stroke="#5b8def" stroke-width="9" stroke-linecap="round" />
    <circle cx="410" cy="165" r="6" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="2.5" />
    <path d="M412 152 l5 -8 M420 156 l9 -5" stroke="{{ $line }}" stroke-width="2.5" stroke-linecap="round" />
    <path d="M472 184 L478 208" stroke="#5b8def" stroke-width="9" stroke-linecap="round" />
    <circle cx="479" cy="211" r="6" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="2.5" />
    <rect x="435" y="172" width="40" height="46" rx="12" fill="#5b8def" stroke="{{ $line }}" stroke-width="3" />
    <path d="M455 178 l-9 -5 v10 z M455 178 l9 -5 v10 z" fill="#e5484d" />
    <circle cx="455" cy="150" r="22" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="3" />
    <path d="M433 152 Q430 125 455 125 Q480 125 477 152 L470 141 L455 143 L440 141 Z" fill="#5b4636" />
    <path d="M443 143 l8 3 M467 143 l-8 3" stroke="{{ $line }}" stroke-width="2.5" stroke-linecap="round" />
    <circle cx="447" cy="151" r="2.6" fill="{{ $line }}" />
    <circle cx="463" cy="151" r="2.6" fill="{{ $line }}" />
    <ellipse cx="441" cy="158" rx="5" ry="3.5" fill="{{ $cheek }}" opacity="0.8" />
    <ellipse cx="469" cy="158" rx="5" ry="3.5" fill="{{ $cheek }}" opacity="0.8" />
    <ellipse cx="455" cy="162" rx="5" ry="6" fill="{{ $mouth }}" stroke="{{ $line }}" stroke-width="2" />

    {{-- ふきだし --}}
    <polygon points="268,106 288,106 318,134" fill="#fffdf7" stroke="{{ $line }}" stroke-width="3" stroke-linejoin="round" />
    <rect x="170" y="72" width="130" height="42" rx="21" fill="#fffdf7" stroke="{{ $line }}" stroke-width="3" />
    <text x="235" y="99" text-anchor="middle" font-size="18" font-weight="900" fill="{{ $line }}">どうも〜！</text>

    <polygon points="502,106 522,106 482,136" fill="#fffdf7" stroke="{{ $line }}" stroke-width="3" stroke-linejoin="round" />
    <rect x="485" y="72" width="150" height="42" rx="21" fill="#fffdf7" stroke="{{ $line }}" stroke-width="3" />
    <text x="560" y="99" text-anchor="middle" font-size="18" font-weight="900" fill="{{ $line }}">なんでやねん！</text>

    {{-- 笑い声 --}}
    <text x="40" y="318" font-size="24" font-weight="900" fill="#d65f24" transform="rotate(-12 40 318)">ワハハ</text>
    <text x="690" y="306" font-size="24" font-weight="900" fill="#d65f24" transform="rotate(10 690 306)">アハハ</text>

    {{-- 観覧する高齢者たち --}}
    @foreach ($audience as $person)
        @php
            ['x' => $x, 'y' => $y] = $person;
        @endphp

        <g>
            <path
                d="M{{ $x - 40 }} {{ $y + 84 }} Q{{ $x - 40 }} {{ $y + 32 }} {{ $x }} {{ $y + 30 }} Q{{ $x + 40 }} {{ $y + 32 }} {{ $x + 40 }} {{ $y + 84 }} Z"
                fill="{{ $person['clothes'] }}"
                stroke="{{ $line }}"
                stroke-width="3"
            />
            <circle cx="{{ $x }}" cy="{{ $y }}" r="28" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="3" />

            @switch($person['hair'])
                @case('bun')
                    <circle cx="{{ $x }}" cy="{{ $y - 36 }}" r="11" fill="{{ $person['hairColor'] }}" stroke="{{ $line }}" stroke-width="3" />
                    <path
                        d="M{{ $x - 29 }} {{ $y + 2 }} Q{{ $x - 31 }} {{ $y - 32 }} {{ $x }} {{ $y - 31 }} Q{{ $x + 31 }} {{ $y - 32 }} {{ $x + 29 }} {{ $y + 2 }} Q{{ $x + 20 }} {{ $y - 14 }} {{ $x }} {{ $y - 16 }} Q{{ $x - 20 }} {{ $y - 14 }} {{ $x - 29 }} {{ $y + 2 }} Z"
                        fill="{{ $person['hairColor'] }}"
                        stroke="{{ $line }}"
                        stroke-width="3"
                        stroke-linejoin="round"
                    />
                    @break
                @case('short')
                    <path
                        d="M{{ $x - 29 }} {{ $y + 2 }} Q{{ $x - 31 }} {{ $y - 32 }} {{ $x }} {{ $y - 31 }} Q{{ $x + 31 }} {{ $y - 32 }} {{ $x + 29 }} {{ $y + 2 }} Q{{ $x + 20 }} {{ $y - 14 }} {{ $x }} {{ $y - 16 }} Q{{ $x - 20 }} {{ $y - 14 }} {{ $x - 29 }} {{ $y + 2 }} Z"
                        fill="{{ $person['hairColor'] }}"
                        stroke="{{ $line }}"
                        stroke-width="3"
                        stroke-linejoin="round"
                    />
                    @break
                @case('curly')
                    @foreach ([[-22, -14, 10], [-12, -25, 10], [0, -29, 10], [12, -25, 10], [22, -14, 10], [-28, 0, 8], [28, 0, 8]] as [$dx, $dy, $r])
                        <circle cx="{{ $x + $dx }}" cy="{{ $y + $dy }}" r="{{ $r }}" fill="{{ $person['hairColor'] }}" stroke="{{ $line }}" stroke-width="2.5" />
                    @endforeach
                    @break
                @case('bald')
                    <ellipse cx="{{ $x - 28 }}" cy="{{ $y + 2 }}" rx="7" ry="10" fill="{{ $person['hairColor'] }}" stroke="{{ $line }}" stroke-width="2.5" />
                    <ellipse cx="{{ $x + 28 }}" cy="{{ $y + 2 }}" rx="7" ry="10" fill="{{ $person['hairColor'] }}" stroke="{{ $line }}" stroke-width="2.5" />
                    <path d="M{{ $x - 9 }} {{ $y - 19 }} q9 -6 18 0" fill="none" stroke="#fffdf7" stroke-width="3" stroke-linecap="round" />
                    @break
                @case('cap')
                    <path
                        d="M{{ $x - 29 }} {{ $y - 14 }} Q{{ $x - 27 }} {{ $y - 40 }} {{ $x }} {{ $y - 39 }} Q{{ $x + 27 }} {{ $y - 40 }} {{ $x + 29 }} {{ $y - 14 }} Z"
                        fill="{{ $person['hairColor'] }}"
                        stroke="{{ $line }}"
                        stroke-width="3"
                        stroke-linejoin="round"
                    />
                    <ellipse cx="{{ $x + 6 }}" cy="{{ $y - 14 }}" rx="30" ry="5" fill="{{ $person['hairColor'] }}" stroke="{{ $line }}" stroke-width="3" />
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

            @if ($person['glasses'])
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

            @if ($person['clap'])
                <circle cx="{{ $x - 7 }}" cy="{{ $y + 52 }}" r="7" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="2.5" />
                <circle cx="{{ $x + 7 }}" cy="{{ $y + 48 }}" r="7" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="2.5" />
                <path
                    d="M{{ $x - 19 }} {{ $y + 40 }} l-6 -5 M{{ $x + 20 }} {{ $y + 36 }} l6 -6"
                    stroke="{{ $line }}"
                    stroke-width="2.5"
                    stroke-linecap="round"
                />
            @endif
        </g>
    @endforeach
</svg>
