<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    //
    
    protected $table = 'materials'; 

    protected $guarded = [];
    public $timestamps = false;

    protected $primaryKey = 'material_id'; 


}
