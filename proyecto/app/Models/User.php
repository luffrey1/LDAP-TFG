<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'guid',
        'domain',
        'ldap_dn',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Check if user has a specific role
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Get the documents uploaded by this user
     */
    public function documentos()
    {
        return $this->hasMany(Documento::class, 'subido_por');
    }

    /**
     * Check if user has admin privileges
     *
     * @return bool
     */
    public function getIsAdminAttribute()
    {
        // Si ya tiene la propiedad is_admin explícitamente definida como true, devolver true
        if ($this->attributes['is_admin'] ?? false) {
            return true;
        }
        
        // También verificar basado en el rol (para compatibilidad)
        return $this->role === 'admin';
    }
    
    /**
     * Set admin status
     */
    public function setIsAdminAttribute($value)
    {
        $this->attributes['is_admin'] = (bool)$value;
        
        // También actualizar el rol para mantener consistencia
        if ((bool)$value && $this->role !== 'admin') {
            $this->role = 'admin';
        }
    }
}