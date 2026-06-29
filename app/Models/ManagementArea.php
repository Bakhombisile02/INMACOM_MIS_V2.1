<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'name',
    'basin',
    'is_active',
    'country',
    'description',
])]
class ManagementArea extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    public function parent()
    {
        return $this->belongsTo(ManagementArea::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ManagementArea::class, 'parent_id');
    }
}
