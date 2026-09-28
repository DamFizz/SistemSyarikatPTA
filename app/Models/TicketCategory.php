<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class TicketCategory extends Model
{
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }
}
