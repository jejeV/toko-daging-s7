@extends('layouts.app')

@section('title', 'SPK Produk (SAW)')

@use('App\Services\Saw\RatingScale')
@use('App\Support\Fmt')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div class="max-w-2xl">
            <h2 class="display text-[24px]">Peringkat produk terbaik</h2>
            <p class="text-[13px] text-muted mt-1">
                Metode Simple Additive Weighting dengan sub-kriteria: nilai asli tiap produk diubah ke rating 1–5,
                dinormalisasi, lalu dijumlah sesuai bobot.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <form action="{{ route('saw.sync') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-secondary" title="Hitung ulang nilai asli dari riwayat kasir dan stok">
                    <i class="fa-solid fa-arrows-rotate text-[11px]"></i> Sinkron data kasir
                </button>
            </form>
            <a href="{{ route('saw.sub-criteria') }}" class="btn btn-secondary">
                <i class="fa-solid fa-layer-group text-[11px]"></i> Sub-kriteria
            </a>
            <a href="{{ route('saw.scores') }}" class="btn btn-primary">
                <i class="fa-solid fa-sliders text-[11px]"></i> Kelola nilai
            </a>
        </div>
    </div>

    @if ($error)
        <div class="callout border-l-accent">
            <i class="fa-solid fa-circle-exclamation text-accent mt-1"></i>
            <div class="flex-1">
                <p class="font-semibold text-ink">Perhitungan SAW gagal</p>
                <p>{{ $error }}</p>
                <p class="mt-1">Tombol di bawah melengkapi kriteria & sub-kriteria bawaan yang belum ada (pengaturan yang sudah diubah tidak ditimpa), lalu menghitung nilai produk dari data kasir.</p>
                <form action="{{ route('saw.sync') }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-list-check text-[11px]"></i> Lengkapi kriteria & hitung nilai
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($sawData)
        @php
            $ranking = $sawData['ranking'];
            $maxRating = $sawData['maxRating'];
            $criteria = $sawData['criteria'];
            $productName = fn ($id) => $sawData['products']->get($id)->name;
        @endphp

        @if (!empty($ranking))
            @php $champion = $ranking[0]; @endphp
            <div class="panel overflow-hidden grid grid-cols-1 md:grid-cols-[auto_1fr_auto]">
                <div class="bg-accent text-white px-6 py-5 flex md:flex-col items-center md:justify-center gap-3 md:gap-0">
                    <span class="text-[11px] font-semibold uppercase tracking-[0.08em] text-white/75">Peringkat</span>
                    <span class="display text-[44px] leading-none">#1</span>
                </div>
                <div class="px-6 py-5 min-w-0">
                    <p class="font-mono text-[11px] text-faint">{{ $champion['product']->code }}</p>
                    <h3 class="display text-[24px] leading-tight mt-0.5">{{ $champion['product']->name }}</h3>
                    <p class="text-[13px] text-muted mt-1.5">
                        {{ $champion['product']->category->name }}
                        · Rp {{ number_format($champion['product']->sell_price, 0, ',', '.') }}/{{ $champion['product']->unit->value }}
                        · rating {{ collect($sawData['matrix'][$champion['alternative']])->join(' · ') }}
                    </p>
                </div>
                <div class="px-6 py-5 md:border-l border-t md:border-t-0 border-line flex md:flex-col justify-between md:justify-center items-center md:items-end">
                    <p class="eyebrow">Nilai akhir</p>
                    <p class="display text-[34px] leading-none text-accent-ink md:mt-1.5">
                        {{ Fmt::qty($champion['scaled']) }}<span class="text-[16px] text-faint font-semibold"> / {{ $maxRating }}</span>
                    </p>
                    <p class="text-[11px] text-faint font-mono md:mt-1">V = {{ number_format($champion['score'], 4) }}</p>
                </div>
            </div>
        @endif

        @if ($sawData['incomplete']->isNotEmpty())
            <div class="callout border-l-warn">
                <i class="fa-solid fa-triangle-exclamation text-warn mt-1"></i>
                <p>
                    <strong>Belum masuk peringkat karena nilainya belum lengkap:</strong>
                    {{ $sawData['incomplete']->pluck('name')->join(', ') }}.
                    <a href="{{ route('saw.scores') }}" class="link ml-1">Lengkapi nilai →</a>
                </p>
            </div>
        @endif

        <section class="panel overflow-hidden">
            <div class="panel-head">
                <h3 class="panel-title">Hasil akhir</h3>
                <span class="text-xs text-faint num">{{ count($ranking) }} produk dievaluasi · nilai 1–{{ $maxRating }} = V × {{ $maxRating }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th class="w-14 text-center">#</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            @foreach ($criteria as $c)
                                <th class="text-center font-mono" title="{{ $c->name }}">{{ $c->code }}</th>
                            @endforeach
                            <th class="text-right">V</th>
                            <th class="min-w-[180px]">Nilai (1–{{ $maxRating }})</th>
                            <th>Rekomendasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ranking as $item)
                            <tr>
                                <td class="text-center">
                                    <span class="display num text-[18px] {{ $item['rank'] === 1 ? 'text-accent' : ($item['rank'] <= 3 ? 'text-ink' : 'text-faint') }}">{{ $item['rank'] }}</span>
                                </td>
                                <td>
                                    <p class="font-semibold">{{ $item['product']->name }}</p>
                                    <p class="font-mono text-[11px] text-faint mt-0.5">{{ $item['product']->code }}</p>
                                </td>
                                <td class="text-muted whitespace-nowrap">{{ $item['product']->category->name }}</td>
                                @foreach ($criteria as $c)
                                    <td class="text-center num text-muted">{{ $sawData['matrix'][$item['alternative']][$c->code] }}</td>
                                @endforeach
                                <td class="text-right font-mono text-xs text-muted">{{ number_format($item['score'], 4) }}</td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-1.5 rounded-full bg-sunken overflow-hidden">
                                            <div class="h-full rounded-full {{ $item['rank'] === 1 ? 'bg-accent' : 'bg-ink/60' }}" style="width: {{ round($item['scaled'] / $maxRating * 100, 1) }}%"></div>
                                        </div>
                                        <span class="display text-[15px] w-9 text-right">{{ Fmt::qty($item['scaled']) }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if ($item['rank'] === 1)
                                        <span class="tag tag-accent">Produk unggulan</span>
                                    @elseif ($item['rank'] <= 3)
                                        <span class="tag tag-ok">Sangat laris</span>
                                    @elseif ($item['rank'] <= 5)
                                        <span class="tag tag-neutral">Stabil</span>
                                    @else
                                        <span class="tag tag-warn">Perlu promosi</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 6 + $criteria->count() }}" class="empty">Belum ada produk yang bisa dihitung. Klik “Sinkron data kasir”.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Sub-kriteria: tabel konversi nilai asli -> rating, format seperti di laporan --}}
        <section class="space-y-3">
            <div class="flex items-baseline justify-between gap-3">
                <h3 class="panel-title">Sub-kriteria (konversi nilai asli → rating)</h3>
                <a href="{{ route('saw.sub-criteria') }}" class="link text-xs">Ubah batas</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($criteria as $c)
                    @php $labels = $c->ratingScale()->labels($c->unit); @endphp
                    <div class="panel overflow-hidden">
                        <div class="px-4 py-3 border-b border-line">
                            <p class="text-[13px] font-semibold"><span class="font-mono text-faint">{{ $c->code }}</span> = {{ $c->name }}</p>
                            <p class="text-[11px] text-faint mt-0.5">
                                {{ ucfirst($c->type->value) }} · bobot {{ (float) $c->weight }} ·
                                {{ $c->type->value === 'cost' ? 'semakin besar nilai asli, semakin tidak prioritas' : 'semakin besar nilai asli, semakin prioritas' }}
                            </p>
                        </div>
                        <table class="tbl text-xs">
                            <thead><tr><th>Nilai asli</th><th class="text-right w-16">Rating</th></tr></thead>
                            <tbody>
                                @foreach ($labels as $rating => $label)
                                    <tr>
                                        <td class="py-1.5! num">{{ $label }}</td>
                                        <td class="py-1.5! text-right display text-[14px]">{{ $rating }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

                <div class="panel p-4 text-xs text-muted leading-relaxed">
                    <p class="font-semibold text-ink text-[13px] mb-1.5">Cara hitung</p>
                    <ol class="list-decimal list-inside space-y-1">
                        <li>Nilai asli tiap kriteria diubah ke rating 1–{{ $maxRating }} memakai tabel di samping.</li>
                        <li>Normalisasi: benefit <span class="font-mono">r = rating ÷ rating tertinggi</span>; cost <span class="font-mono">r = rating terendah ÷ rating</span>.</li>
                        <li><span class="font-mono">V = Σ (bobot × r)</span>, bernilai 0–1.</li>
                        <li>Nilai akhir 1–{{ $maxRating }} = <span class="font-mono">V × {{ $maxRating }}</span>.</li>
                    </ol>
                </div>
            </div>
        </section>

        {{-- Matriks perhitungan --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            @foreach ([
                ['title' => 'Nilai asli', 'note' => 'Dari data kasir & stok', 'rows' => $sawData['raw'], 'format' => fn ($v, $c) => RatingScale::format($v, $c->unit === 'rp' ? null : $c->unit)],
                ['title' => 'Matriks keputusan (X)', 'note' => 'Rating sub-kriteria', 'rows' => $sawData['matrix'], 'format' => fn ($v) => $v],
                ['title' => 'Ternormalisasi (R)', 'note' => 'Benefit: x/max · Cost: min/x', 'rows' => $sawData['normalized'], 'format' => fn ($v) => number_format($v, 4)],
            ] as $m)
                <section class="panel overflow-hidden">
                    <div class="panel-head">
                        <h3 class="panel-title">{{ $m['title'] }}</h3>
                        <span class="text-[11px] text-faint">{{ $m['note'] }}</span>
                    </div>
                    <div class="overflow-auto max-h-[420px]">
                        <table class="tbl text-xs">
                            <thead class="sticky top-0">
                                <tr>
                                    <th>Produk</th>
                                    @foreach ($criteria as $c)
                                        <th class="text-right font-mono" title="{{ $c->name }}">{{ $c->code }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($m['rows'] as $productId => $row)
                                    <tr>
                                        <td class="py-2! font-medium whitespace-nowrap max-w-[160px] truncate" title="{{ $productName($productId) }}">{{ $productName($productId) }}</td>
                                        @foreach ($criteria as $c)
                                            <td class="py-2! text-right font-mono text-muted whitespace-nowrap">
                                                {{ isset($row[$c->code]) ? $m['format']($row[$c->code], $c) : '—' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>

        <section class="panel overflow-hidden">
            <div class="panel-head">
                <h3 class="panel-title">Kriteria & bobot (W)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th class="w-20">Kode</th>
                            <th>Kriteria</th>
                            <th>Tipe</th>
                            <th class="min-w-[220px]">Bobot</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($criteria as $crit)
                            <tr>
                                <td class="font-mono font-semibold">{{ $crit->code }}</td>
                                <td>{{ $crit->name }}</td>
                                <td>
                                    <span class="tag {{ $crit->type->value === 'benefit' ? 'tag-ok' : 'tag-accent' }}">{{ ucfirst($crit->type->value) }}</span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-1.5 rounded-full bg-sunken overflow-hidden">
                                            <div class="h-full rounded-full bg-ink/60" style="width: {{ (float) $crit->weight * 100 }}%"></div>
                                        </div>
                                        <span class="font-mono text-xs w-20 text-right">{{ (float) $crit->weight }} <span class="text-faint">({{ (float) $crit->weight * 100 }}%)</span></span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

    @endif

</div>
@endsection
