<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $connection = 'landlord';

    protected $fillable = [
        'name',
        'connection_code',
        'database',
        'db_username',
        'db_password',
        'owner_name',
        'owner_email',
        'owner_phone',
        'is_active',
    ];

    protected $hidden = ['database', 'db_username', 'db_password'];

    protected $casts = [
        'database'    => 'encrypted',
        'db_username' => 'encrypted',
        'db_password' => 'encrypted',
        'is_active'   => 'boolean',
    ];

    public function meliAccounts(): HasMany
    {
        return $this->hasMany(StoreMeliAccount::class);
    }
}
