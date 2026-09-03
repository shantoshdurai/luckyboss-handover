<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployerNote extends Model
{
    protected $fillable = ['company_id', 'user_id', 'note'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
