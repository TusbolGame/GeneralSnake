<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class SMSCode extends Model
{
    public $timestamps = false;
    protected $table = 's_m_s_codes';
}
