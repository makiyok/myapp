@php
    /**
     * 踊る幼稚園児5人。(x, y) は顔の中心。
     *
     * @var list<array{x: int, y: int, pose: string, smock: string, pigtails: bool, eyes: string}> $children
     */
    $children = [
        ['x' => 240, 'y' => 182, 'pose' => 'up', 'smock' => '#f7a8a0', 'pigtails' => true, 'eyes' => 'happy'],
        ['x' => 320, 'y' => 182, 'pose' => 'kick', 'smock' => '#8fb3de', 'pigtails' => false, 'eyes' => 'dot'],
        ['x' => 400, 'y' => 182, 'pose' => 'up', 'smock' => '#7cae7a', 'pigtails' => false, 'eyes' => 'happy'],
        ['x' => 480, 'y' => 182, 'pose' => 'side', 'smock' => '#e9a23b', 'pigtails' => true, 'eyes' => 'dot'],
        ['x' => 560, 'y' => 182, 'pose' => 'kick', 'smock' => '#b59ad6', 'pigtails' => false, 'eyes' => 'happy'],
    ];

    /**
     * 楽しむ高齢者7人（左右の2人と、前の列の5人）。
     *
     * @var list<array{x: int, y: int, hair: string, hairColor: string, clothes: string, glasses: bool, pose: string}> $audience
     */
    $audience = [
        ['x' => 70, 'y' => 245, 'hair' => 'short', 'hairColor' => '#f2efea', 'clothes' => '#6fa8a0', 'glasses' => true, 'pose' => 'wave'],
        ['x' => 730, 'y' => 245, 'hair' => 'bun', 'hairColor' => '#d9d4cc', 'clothes' => '#d98c8c', 'glasses' => false, 'pose' => 'clap'],
        ['x' => 100, 'y' => 430, 'hair' => 'cap', 'hairColor' => '#6d7f9e', 'clothes' => '#e9a23b', 'glasses' => false, 'pose' => 'clap'],
        ['x' => 250, 'y' => 430, 'hair' => 'curly', 'hairColor' => '#e8e4de', 'clothes' => '#8fb3de', 'glasses' => true, 'pose' => 'wave'],
        ['x' => 400, 'y' => 430, 'hair' => 'bald', 'hairColor' => '#d9d4cc', 'clothes' => '#7cae7a', 'glasses' => true, 'pose' => 'clap'],
        ['x' => 550, 'y' => 430, 'hair' => 'bun', 'hairColor' => '#f2efea', 'clothes' => '#b59ad6', 'glasses' => false, 'pose' => 'wave'],
        ['x' => 700, 'y' => 430, 'hair' => 'short', 'hairColor' => '#c8c3bd', 'clothes' => '#c9a27c', 'glasses' => false, 'pose' => 'clap'],
    ];

    $flagColors = ['#d65f24', '#e9a23b', '#7cae7a', '#8fb3de', '#f7a8a0'];

    /** @var list<array{0: int, 1: int, 2: string}> $notes 音符の位置と色 */
    $notes = [[150, 120, '#d65f24'], [220, 100, '#5b8def'], [585, 105, '#7cae7a'], [650, 128, '#d65f24'], [200, 352, '#b59ad6'], [610, 348, '#e9a23b']];

    $line = '#46342a';
    $skin = '#f6d3b3';
    $cheek = '#f7a8a0';
    $mouth = '#b94a14';
    $hat = '#f5c84c';
@endphp

<svg
    viewBox="0 0 800 500"
    xmlns="http://www.w3.org/2000/svg"
    role="img"
    aria-labelledby="dance-illustration-title"
    {{ $attributes }}
>
    <title id="dance-illustration-title">ダンスする幼稚園児と、それを楽しむ高齢者のイラスト</title>

    {{-- 壁と床 --}}
    <rect width="800" height="300" fill="#fff6e6" />
    <rect y="300" width="800" height="200" fill="#f8dcb4" />

    {{-- 三角の旗の飾り --}}
    <path d="M40 40 Q400 140 760 40" fill="none" stroke="{{ $line }}" stroke-width="2.5" />
    @foreach (range(1, 9) as $i)
        @php
            $t = $i / 10;
            $flagX = 40 + 720 * $t;
            $flagY = 40 + 100 * $t * (1 - $t);
        @endphp
        <polygon
            points="{{ $flagX - 15 }},{{ $flagY }} {{ $flagX + 15 }},{{ $flagY }} {{ $flagX }},{{ $flagY + 30 }}"
            fill="{{ $flagColors[$i % count($flagColors)] }}"
            stroke="{{ $line }}"
            stroke-width="2.5"
            stroke-linejoin="round"
        />
    @endforeach

    {{-- 看板 --}}
    <rect x="300" y="84" width="200" height="40" rx="20" fill="#fffdf7" stroke="{{ $line }}" stroke-width="3" />
    <text x="400" y="111" text-anchor="middle" font-size="19" font-weight="900" fill="{{ $line }}">みんなでダンス！</text>

    {{-- 音符 --}}
    @foreach ($notes as [$noteX, $noteY, $noteColor])
        <ellipse cx="{{ $noteX }}" cy="{{ $noteY }}" rx="8" ry="6" fill="{{ $noteColor }}" transform="rotate(-20 {{ $noteX }} {{ $noteY }})" />
        <path d="M{{ $noteX + 7 }} {{ $noteY - 2 }} V{{ $noteY - 28 }} q10 5 11 15" fill="none" stroke="{{ $noteColor }}" stroke-width="3" stroke-linecap="round" />
    @endforeach

    {{-- 踊る幼稚園児 --}}
    @foreach ($children as $child)
        @php
            ['x' => $x, 'y' => $y, 'smock' => $smock] = $child;

            $arms = match ($child['pose']) {
                'up' => [[$x - 12, $y + 30, $x - 30, $y - 6], [$x + 12, $y + 30, $x + 30, $y - 6]],
                'side' => [[$x - 12, $y + 30, $x - 38, $y + 20], [$x + 12, $y + 30, $x + 38, $y + 20]],
                default => [[$x - 12, $y + 30, $x - 30, $y - 2], [$x + 12, $y + 32, $x + 32, $y + 46]],
            };

            $legs = $child['pose'] === 'kick'
                ? [[$x - 8, $y + 70, $x - 9, $y + 102], [$x + 8, $y + 70, $x + 24, $y + 88]]
                : [[$x - 8, $y + 70, $x - 11, $y + 102], [$x + 8, $y + 70, $x + 11, $y + 102]];
        @endphp

        <g>
            @foreach ($legs as [$x1, $y1, $x2, $y2])
                <path d="M{{ $x1 }} {{ $y1 }} L{{ $x2 }} {{ $y2 }}" stroke="{{ $skin }}" stroke-width="8" stroke-linecap="round" />
                <ellipse cx="{{ $x2 }}" cy="{{ $y2 + 2 }}" rx="8" ry="4.5" fill="#e5484d" stroke="{{ $line }}" stroke-width="2" />
            @endforeach

            @foreach ($arms as [$x1, $y1, $x2, $y2])
                <path d="M{{ $x1 }} {{ $y1 }} L{{ $x2 }} {{ $y2 }}" stroke="{{ $smock }}" stroke-width="8" stroke-linecap="round" />
                <circle cx="{{ $x2 }}" cy="{{ $y2 }}" r="5.5" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="2" />
            @endforeach

            {{-- スモック --}}
            <path
                d="M{{ $x - 14 }} {{ $y + 20 }} L{{ $x + 14 }} {{ $y + 20 }} L{{ $x + 23 }} {{ $y + 72 }} L{{ $x - 23 }} {{ $y + 72 }} Z"
                fill="{{ $smock }}"
                stroke="{{ $line }}"
                stroke-width="3"
                stroke-linejoin="round"
            />
            <path d="M{{ $x - 8 }} {{ $y + 21 }} Q{{ $x }} {{ $y + 30 }} {{ $x + 8 }} {{ $y + 21 }}" fill="#fffdf7" stroke="{{ $line }}" stroke-width="2" />

            {{-- 顔 --}}
            @if ($child['pigtails'])
                <circle cx="{{ $x - 22 }}" cy="{{ $y + 4 }}" r="7" fill="#33261e" />
                <circle cx="{{ $x + 22 }}" cy="{{ $y + 4 }}" r="7" fill="#33261e" />
            @endif
            <circle cx="{{ $x }}" cy="{{ $y }}" r="20" fill="{{ $skin }}" stroke="{{ $line }}" stroke-width="3" />
            <path d="M{{ $x - 17 }} {{ $y - 6 }} Q{{ $x }} {{ $y - 2 }} {{ $x + 17 }} {{ $y - 6 }} L{{ $x + 18 }} {{ $y - 10 }} L{{ $x - 18 }} {{ $y - 10 }} Z" fill="#33261e" />

            @if ($child['eyes'] === 'happy')
                <path d="M{{ $x - 11 }} {{ $y + 3 }} q4 -5 8 0 M{{ $x + 3 }} {{ $y + 3 }} q4 -5 8 0" fill="none" stroke="{{ $line }}" stroke-width="2.5" stroke-linecap="round" />
            @else
                <circle cx="{{ $x - 7 }}" cy="{{ $y + 2 }}" r="2.5" fill="{{ $line }}" />
                <circle cx="{{ $x + 7 }}" cy="{{ $y + 2 }}" r="2.5" fill="{{ $line }}" />
            @endif

            <ellipse cx="{{ $x - 13 }}" cy="{{ $y + 9 }}" rx="4.5" ry="3" fill="{{ $cheek }}" opacity="0.9" />
            <ellipse cx="{{ $x + 13 }}" cy="{{ $y + 9 }}" rx="4.5" ry="3" fill="{{ $cheek }}" opacity="0.9" />
            <path d="M{{ $x - 6 }} {{ $y + 9 }} Q{{ $x }} {{ $y + 18 }} {{ $x + 6 }} {{ $y + 9 }} Z" fill="{{ $mouth }}" stroke="{{ $line }}" stroke-width="2" stroke-linejoin="round" />

            {{-- 黄色い帽子 --}}
            <path
                d="M{{ $x - 19 }} {{ $y - 9 }} Q{{ $x - 19 }} {{ $y - 32 }} {{ $x }} {{ $y - 32 }} Q{{ $x + 19 }} {{ $y - 32 }} {{ $x + 19 }} {{ $y - 9 }} Z"
                fill="{{ $hat }}"
                stroke="{{ $line }}"
                stroke-width="3"
                stroke-linejoin="round"
            />
            <ellipse cx="{{ $x }}" cy="{{ $y - 9 }}" rx="26" ry="5" fill="{{ $hat }}" stroke="{{ $line }}" stroke-width="3" />
        </g>
    @endforeach

    {{-- 右の人の椅子の背もたれ（高齢者より先に描く） --}}
    <rect x="696" y="262" width="68" height="74" rx="12" fill="#c9a27c" stroke="{{ $line }}" stroke-width="3" />

    {{-- 楽しむ高齢者たち --}}
    @foreach ($audience as $person)
        <x-illustration.elderly
            :x="$person['x']"
            :y="$person['y']"
            :hair="$person['hair']"
            :hair-color="$person['hairColor']"
            :clothes="$person['clothes']"
            :glasses="$person['glasses']"
            :pose="$person['pose']"
        />
    @endforeach

    {{-- 左の人の車いす --}}
    <circle cx="96" cy="318" r="30" fill="none" stroke="#6d7f9e" stroke-width="5" />
    <circle cx="96" cy="318" r="4" fill="#6d7f9e" />

    {{-- ふきだし --}}
    <polygon points="664,196 684,196 712,214" fill="#fffdf7" stroke="{{ $line }}" stroke-width="3" stroke-linejoin="round" />
    <rect x="612" y="160" width="140" height="40" rx="20" fill="#fffdf7" stroke="{{ $line }}" stroke-width="3" />
    <text x="682" y="186" text-anchor="middle" font-size="17" font-weight="900" fill="{{ $line }}">じょうず〜！</text>
</svg>
