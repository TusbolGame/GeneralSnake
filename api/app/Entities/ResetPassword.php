<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class ResetPassword extends Model
{
    //
    protected $table = 'reset_password';
    public $timestamps = false;
}
