<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    //
    
    protected $table = 'manufacturers'; 

    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'manufacturer_id'; 


}
