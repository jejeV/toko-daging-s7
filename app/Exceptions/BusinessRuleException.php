<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar saat aturan bisnis dilanggar (stok kurang, uang bayar kurang, dll).
 * Pesannya aman ditampilkan langsung ke user.
 */
class BusinessRuleException extends RuntimeException
{
}
