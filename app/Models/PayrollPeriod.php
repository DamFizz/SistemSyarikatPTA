<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['period_name', 'start_date', 'end_date', 'status'])]
class PayrollPeriod extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }
}
