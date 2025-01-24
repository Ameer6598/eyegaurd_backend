<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Style extends Model
{
    //
    
    protected $table = 'styles'; 

    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'style_id'; 


}
