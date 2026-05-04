<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $fillable = [
        'file_name',
        'file_path',
        'rows_count',
        'analysis_data',
    ];

    protected $casts = [
        'analysis_data' => 'array',
    ];
}
