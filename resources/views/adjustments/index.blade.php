@extends('layouts.app')

@section('title', 'Penyesuaian Stok')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div>
            <h2 class="display text-[24px]">Penyesuaian & opname</h2>
            <p class="text-[13px] text-muted mt-1">Susut pemotongan, daging rusak, dan koreksi hasil stok opname fisik.</p>
        </div>
        <a href="{{ route('adjustments.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus text-[11px]"></i> Catat penyesuaian
        </a>
    </div>

    <section class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Produk</th>
                        <th>Jenis</th>
                        <th class="text-right">Selisih</th>
                        <th>Alasan</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($adjustments as $adj)
                        <tr>
                            <td class="text-muted whitespace-nowrap num">{{ $adj->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                            <td>
                                <p class="font-semibold">{{ $adj->product->name }}</p>
                                <p class="font-mono text-[11px] text-faint mt-0.5">{{ $adj->product->code }}</p>
                            </td>
                            <td>
                                @if ($adj->type === \App\Enums\AdjustmentType::Susut)
                                    <span class="tag tag-warn">Susut</span>
                                @elseif ($adj->type === \App\Enums\AdjustmentType::Rusak)
                                    <span class="tag tag-accent">Rusak</span>
                                @else
                                    <span class="tag tag-neutral">Opname</span>
                                @endif
                            </td>
                            <td class="text-right num font-semibold whitespace-nowrap {{ $adj->qty < 0 ? 'text-accent' : ($adj->qty > 0 ? 'text-ok' : 'text-faint') }}">
                                {{ $adj->qty > 0 ? '+' : '' }}{{ (float) $adj->qty }} <span class="font-normal text-faint">{{ $adj->product->unit->value }}</span>
                            </td>
                            <td class="text-muted max-w-xs">{{ $adj->reason }}</td>
                            <td class="text-muted whitespace-nowrap">{{ $adj->user->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">Belum ada penyesuaian stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $adjustments->links('partials.pagination') }}
    </section>

</div>
@endsection
