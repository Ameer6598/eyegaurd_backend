<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RimType extends Model
{
    //
    
    protected $table = 'rim_types'; 

    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'rim_type_id'; 


}
