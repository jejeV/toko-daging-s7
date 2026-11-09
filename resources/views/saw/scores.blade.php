@extends('layouts.app')

@section('title', 'Nilai Kriteria Produk')

@section('content')
<div class="space-y-5">

    <a href="{{ route('saw.index') }}" class="back-link">
        <i class="fa-solid fa-arrow-left text-xs"></i> Peringkat SAW
    </a>

    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div class="max-w-2xl">
            <h2 class="display text-[24px]">Nilai kriteria produk</h2>
            <p class="text-[13px] text-muted mt-1">
                Isi manual atau isi otomatis dari riwayat kasir.
                <span class="font-mono text-xs">C1</span> qty terjual,
                <span class="font-mono text-xs">C2</span> omzet,
                <span class="font-mono text-xs">C3</span> margin %,
                <span class="font-mono text-xs">C4</span> frekuensi transaksi,
                <span class="font-mono text-xs">C5</span> susut/rusak.
            </p>
        </div>
        <form action="{{ route('saw.sync') }}" method="POST" class="shrink-0">
            @csrf
            <button type="submit" class="btn btn-secondary" title="Isi nilai otomatis dari penjualan kasir">
                <i class="fa-solid fa-arrows-rotate text-[11px]"></i> Isi otomatis dari kasir
            </button>
        </form>
    </div>

    <form action="{{ route('saw.scores.update') }}" method="POST" class="panel overflow-hidden">
        @csrf

        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="min-w-[220px]">Produk</th>
                        @foreach ($criteria as $c)
                            <th class="text-right min-w-[130px] normal-case! tracking-normal!">
                                <span class="block text-[12px] text-ink font-semibold">{{ $c->name }}</span>
                                <span class="block text-[10px] font-mono text-faint font-normal">{{ $c->code }} · {{ $c->type->value }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $prod)
                        <tr>
                            <td>
                                <p class="font-semibold">{{ $prod->name }}</p>
                                <p class="text-[11px] text-faint mt-0.5"><span class="font-mono">{{ $prod->code }}</span> · Rp {{ number_format($prod->sell_price, 0, ',', '.') }}</p>
                            </td>
                            @foreach ($criteria as $c)
                                <td class="text-right py-2.5!">
                                    <input type="number" step="0.01" min="0"
                                        name="scores[{{ $prod->id }}][{{ $c->id }}]"
                                        value="{{ isset($scoresMatrix[$prod->id][$c->id]) ? $scoresMatrix[$prod->id][$c->id] : '' }}"
                                        placeholder="0.00"
                                        class="field w-28 ml-auto text-right font-mono text-xs">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-line bg-sunken/40">
            <a href="{{ route('saw.index') }}" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan nilai</button>
        </div>
    </form>

</div>
@endsection
