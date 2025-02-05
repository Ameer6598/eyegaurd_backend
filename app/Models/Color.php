<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    //
    
    protected $table = 'colors'; 

    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'color_id'; 


}
