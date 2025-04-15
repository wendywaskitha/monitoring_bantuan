<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use App\Models\Kelompok;
use App\Models\PelaporanHasil;
use App\Models\DistribusiBantuan;
use App\Models\MonitoringPemanfaatan;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    // Implement canAccessPanel method for Filament
    public function canAccessPanel(Panel $panel): bool
    {
        // Example: Allow access if user has any role (adjust as needed)
        // Or check specific roles/permissions: return $this->hasRole(['Admin', 'Petugas Lapangan']);
         return true; // Allow all authenticated users for now, refine later
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relasi ke DistribusiBantuan (Petugas yg mencatat)
    public function distribusiBantuans()
    {
        return $this->hasMany(DistribusiBantuan::class);
    }

    // Relasi ke MonitoringPemanfaatan (Petugas yg memonitor)
    public function monitoringPemanfaatans()
    {
        return $this->hasMany(MonitoringPemanfaatan::class);
    }

    // Relasi ke PelaporanHasil (Petugas yg melapor)
    public function pelaporanHasils()
    {
        return $this->hasMany(PelaporanHasil::class);
    }

    public function kelompokYangDitangani(): HasMany
    {
        return $this->hasMany(Kelompok::class, 'user_id');
    }
}
