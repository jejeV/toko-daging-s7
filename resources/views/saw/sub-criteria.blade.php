@extends('layouts.app')

@section('title', 'Sub-kriteria SAW')

@use('App\Services\Saw\RatingScale')

@section('content')
<div class="space-y-5">

    <a href="{{ route('saw.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left text-xs"></i> Peringkat SAW
    </a>

    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div class="max-w-2xl">
            <h2 class="display text-[24px]">Sub-kriteria</h2>
            <p class="text-[13px] text-muted mt-1">
                Nilai asli tiap produk diubah menjadi rating 1–5 sebelum dihitung SAW.
                Isi <strong class="text-ink font-semibold">batas bawah</strong> tiap rating: nilai ≥ batas itu mendapat rating tersebut.
                Rating 1 = semua nilai di bawah batas rating 2.
            </p>
        </div>
        <form action="{{ route('saw.sub-criteria.reset') }}" method="POST" class="shrink-0"
              onsubmit="return confirm('Kembalikan semua kriteria dan sub-kriteria ke pengaturan bawaan?')">
            @csrf
            <button type="submit" class="btn btn-ghost">
                <i class="fa-solid fa-rotate-left text-[11px]"></i> Kembalikan bawaan
            </button>
        </form>
    </div>

    <form action="{{ route('saw.sub-criteria.update') }}" method="POST" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            @foreach ($criteria as $c)
                @php
                    $scale = $c->ratingScale();
                    $labels = $scale?->labels($c->unit) ?? [];
                    $bounds = $c->subCriteria->pluck('min_value', 'rating');
                    $isCost = $c->type->value === 'cost';
                @endphp
                <section class="panel overflow-hidden">
                    <div class="panel-head h-auto py-3">
                        <div>
                            <h3 class="panel-title"><span class="font-mono text-faint mr-1">{{ $c->code }}</span> {{ $c->name }}</h3>
                            <p class="text-[11px] text-faint mt-0.5">
                                {{ $isCost ? 'Cost — semakin besar nilainya semakin buruk' : 'Benefit — semakin besar semakin prioritas' }}
                                · bobot {{ (float) $c->weight }}
                            </p>
                        </div>
                        <span class="tag {{ $isCost ? 'tag-accent' : 'tag-ok' }}">{{ ucfirst($c->type->value) }}</span>
                    </div>
                    <table class="tbl">
                        <thead>
                            <tr><th class="w-16">Nilai</th><th>Rentang (nilai asli)</th><th class="w-44">Batas bawah</th></tr>
                        </thead>
                        <tbody>
                            @foreach ([5, 4, 3, 2, 1] as $rating)
                                <tr>
                                    <td><span class="display text-[18px] {{ $rating === 5 ? ($isCost ? 'text-accent' : 'text-ok') : '' }}">{{ $rating }}</span></td>
                                    <td class="text-muted num">{{ $labels[$rating] ?? '—' }}</td>
                                    <td class="py-2!">
                                        @if ($rating === 1)
                                            <span class="text-xs text-faint">di bawah batas nilai 2</span>
                                        @else
                                            <div class="relative">
                                                @if ($c->unit === 'rp')
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-faint">Rp</span>
                                                @endif
                                                <input type="number" step="any" min="0" required
                                                       name="bounds[{{ $c->id }}][{{ $rating }}]"
                                                       value="{{ old("bounds.{$c->id}.{$rating}", $bounds[$rating] ?? '') }}"
                                                       aria-label="Batas bawah nilai {{ $rating }} {{ $c->code }}"
                                                       class="field text-right num {{ $c->unit === 'rp' ? 'pl-9' : '' }} {{ $errors->has("bounds.{$c->id}.{$rating}") ? 'border-accent' : '' }}">
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            @endforeach
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('saw.index') }}" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan & hitung ulang</button>
        </div>
    </form>

</div>
@endsection
