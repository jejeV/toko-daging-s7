<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';         // pembelian
    case Out = 'out';       // penjualan
    case Adjust = 'adjust'; // susut, rusak, opname
}
