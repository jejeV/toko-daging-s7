{{--
    Grafik kolom satu seri, HTML/CSS + Alpine (ikut tema terang/gelap lewat token warna).

    items: list of [
        'value' => float,            tinggi kolom
        'axis'  => ?string,          label sumbu X (null = kosong)
        'title' => string,           baris pertama tooltip & aria-label
        'lines' => list<string>,     baris tooltip berikutnya
        'shade' => bool,             latar arsir (mis. akhir pekan)
    ]
--}}
@props([
    'items' => [],
    'height' => 'h-56',
    'color' => 'bg-accent',
    'peak' => true,
    'label' => 'Grafik',
])

@use('App\Support\ChartAxis')
@use('App\Support\Fmt')

@php
    $values = array_column($items, 'value');
    $ceiling = ChartAxis::ceiling(max($values ?: [0]));
    $peakIndex = $peak && max($values ?: [0]) > 0 ? array_search(max($values), $values, true) : null;
    $tips = array_map(fn ($i) => ['title' => $i['title'], 'lines' => $i['lines'] ?? []], $items);
@endphp

<div x-data="{ hover: null, tips: @js($tips) }" role="group" aria-label="{{ $label }}" {{ $attributes }}>
    <div class="relative {{ $height }} ml-11">
        @foreach (ChartAxis::ticks($ceiling) as $tick)
            <div class="absolute inset-x-0 border-t border-line" style="bottom: {{ $tick / $ceiling * 100 }}%">
                <span class="absolute -left-11 w-9 -translate-y-1/2 text-right text-[10px] text-faint num">{{ Fmt::short($tick) }}</span>
            </div>
        @endforeach

        <div class="absolute inset-0 flex items-end gap-0.5">
            @foreach ($items as $i => $item)
                @php $pct = $item['value'] > 0 ? max(1, $item['value'] / $ceiling * 100) : 0; @endphp
                <div class="relative flex-1 h-full flex items-end justify-center outline-none focus-visible:bg-sunken {{ ! empty($item['shade']) ? 'bg-sunken/70' : '' }}"
                     tabindex="0"
                     aria-label="{{ $item['title'] }}: {{ implode(', ', $item['lines'] ?? []) }}"
                     @mouseenter="hover = {{ $i }}" @mouseleave="hover = null"
                     @focus="hover = {{ $i }}" @blur="hover = null">
                    <div class="w-full max-w-6 rounded-t-sm {{ $color }} transition-opacity duration-100"
                         :class="hover !== null && hover !== {{ $i }} && 'opacity-35'"
                         style="height: {{ $pct }}%"></div>
                    @if ($i === $peakIndex)
                        <span class="absolute left-1/2 -translate-x-1/2 text-[10px] font-semibold text-muted whitespace-nowrap num pointer-events-none"
                              style="bottom: calc({{ $pct }}% + 4px)" x-show="hover === null">{{ Fmt::short($item['value']) }}</span>
                    @endif
                </div>
            @endforeach
        </div>

        <div x-show="hover !== null" x-cloak
             class="absolute top-0 z-10 -translate-x-1/2 pointer-events-none panel shadow-lg px-3 py-2 min-w-36"
             :style="hover !== null && `left: ${Math.min(85, Math.max(15, (hover + 0.5) / tips.length * 100))}%`">
            <template x-if="hover !== null">
                <div>
                    <p class="text-[11px] text-faint" x-text="tips[hover].title"></p>
                    <template x-for="(line, n) in tips[hover].lines" :key="n">
                        <p class="num" :class="n === 0 ? 'text-[14px] font-semibold mt-0.5' : 'text-[11px] text-muted'" x-text="line"></p>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <div class="flex gap-0.5 ml-11 mt-1.5" aria-hidden="true">
        @foreach ($items as $item)
            <span class="flex-1 text-center text-[10px] text-faint num whitespace-nowrap">{{ $item['axis'] ?? '' }}</span>
        @endforeach
    </div>
</div>
