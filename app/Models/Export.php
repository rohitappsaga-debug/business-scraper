<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'file_path',
        'type',
    ];
}
