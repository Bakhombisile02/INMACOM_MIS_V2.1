<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StationCapability extends Model
{
    protected $table = 'station_capabilities';

    public $timestamps = false;

    protected $primaryKey = 'station_id';

    public $incrementing = false;

    protected $keyType = 'string';
}
