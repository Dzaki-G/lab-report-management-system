<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // IMPORTANT: match your table
    protected $primaryKey = 'user_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'role_id',
        'username',
        'email',
        'password',
        'full_name',
        'is_active',
        'signature_drive_file_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role_id'  => 'integer',
            'is_active' => 'boolean',
        ];
    }
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }


}
