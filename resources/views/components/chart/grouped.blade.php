{{--
    Grafik kolom berkelompok (beberapa seri, satu sumbu & satuan yang sama).

    labels: list<string>                         label sumbu X per kelompok
    series: list<['name' => string, 'color' => 'bg-…', 'values' => list<float>]>
--}}
@props([
    'labels' => [],
    'series' => [],
    'height' => 'h-56',
    'label' => 'Grafik',
])

@use('App\Support\ChartAxis')
@use('App\Support\Fmt')

@php
    $all = array_merge(...array_map(fn ($s) => $s['values'], $series ?: [['values' => [0]]]));
    $ceiling = ChartAxis::ceiling(max($all ?: [0]));
    $tips = array_map(fn ($i) => [
        'title' => $labels[$i],
        'rows' => array_map(fn ($s) => [$s['name'], Fmt::rupiah($s['values'][$i]), $s['color']], $series),
    ], array_keys($labels));
@endphp

<div x-data="{ hover: null, tips: @js($tips) }" role="group" aria-label="{{ $label }}" {{ $attributes }}>
    <div class="flex flex-wrap gap-x-4 gap-y-1 mb-3 text-xs text-muted">
        @foreach ($series as $s)
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm {{ $s['color'] }}"></span>{{ $s['name'] }}</span>
        @endforeach
    </div>

    <div class="relative {{ $height }} ml-11">
        @foreach (ChartAxis::ticks($ceiling) as $tick)
            <div class="absolute inset-x-0 border-t border-line" style="bottom: {{ $tick / $ceiling * 100 }}%">
                <span class="absolute -left-11 w-9 -translate-y-1/2 text-right text-[10px] text-faint num">{{ Fmt::short($tick) }}</span>
            </div>
        @endforeach

        <div class="absolute inset-0 flex items-end gap-2">
            @foreach ($labels as $i => $groupLabel)
                <div class="relative flex-1 h-full flex items-end justify-center gap-0.5 outline-none focus-visible:bg-sunken"
                     tabindex="0"
                     aria-label="{{ $groupLabel }}: {{ collect($series)->map(fn ($s) => $s['name'].' '.Fmt::rupiah($s['values'][$i]))->join(', ') }}"
                     @mouseenter="hover = {{ $i }}" @mouseleave="hover = null"
                     @focus="hover = {{ $i }}" @blur="hover = null">
                    @foreach ($series as $s)
                        @php $value = $s['values'][$i]; @endphp
                        <div class="flex-1 max-w-6 rounded-t-sm {{ $s['color'] }} transition-opacity duration-100"
                             :class="hover !== null && hover !== {{ $i }} && 'opacity-35'"
                             style="height: {{ $value > 0 ? max(1, $value / $ceiling * 100) : 0 }}%"></div>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div x-show="hover !== null" x-cloak
             class="absolute top-0 z-10 -translate-x-1/2 pointer-events-none panel shadow-lg px-3 py-2 min-w-44"
             :style="hover !== null && `left: ${Math.min(80, Math.max(20, (hover + 0.5) / tips.length * 100))}%`">
            <template x-if="hover !== null">
                <div>
                    <p class="text-[11px] text-faint mb-1" x-text="tips[hover].title"></p>
                    <template x-for="row in tips[hover].rows" :key="row[0]">
                        <p class="flex items-center justify-between gap-4 text-[12px]">
                            <span class="inline-flex items-center gap-1.5 text-muted"><span class="w-2 h-2 rounded-sm" :class="row[2]"></span><span x-text="row[0]"></span></span>
                            <span class="num font-semibold" x-text="row[1]"></span>
                        </p>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <div class="flex gap-2 ml-11 mt-1.5" aria-hidden="true">
        @foreach ($labels as $groupLabel)
            <span class="flex-1 text-center text-[10px] text-faint num whitespace-nowrap overflow-hidden text-ellipsis">{{ $groupLabel }}</span>
        @endforeach
    </div>
</div>
