{{--
    Batang horizontal untuk perbandingan antar kategori (satu seri).

    items: list<['label' => string, 'value' => float, 'display' => string, 'note' => ?string]>
--}}
@props([
    'items' => [],
    'color' => 'bg-accent',
])

@php $max = max(array_column($items, 'value') ?: [0]) ?: 1; @endphp

<div {{ $attributes->merge(['class' => 'space-y-3.5']) }}>
    @foreach ($items as $item)
        <div>
            <div class="flex items-baseline justify-between gap-3 text-[13px]">
                <span class="font-medium truncate">{{ $item['label'] }}</span>
                <span class="num text-muted shrink-0">
                    {{ $item['display'] }}
                    @if (! empty($item['note']))
                        <span class="text-faint text-[11px]">· {{ $item['note'] }}</span>
                    @endif
                </span>
            </div>
            <div class="mt-1.5 h-2 rounded-r-sm bg-sunken">
                <div class="h-full rounded-r-sm {{ $color }}" style="width: {{ max(0, $item['value']) / $max * 100 }}%"></div>
            </div>
        </div>
    @endforeach
</div>
