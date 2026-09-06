<?php

namespace App\Models;

use App\Enums\Permission;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $label
 * @property array<int, string> $permissions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'label', 'permissions'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public const string ADMIN = 'admin';

    public const string VIEWER = 'viewer';

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function isSuperRole(): bool
    {
        return $this->name === self::ADMIN;
    }

    /**
     * The permission values this role grants, expanded for the super role.
     *
     * @return array<int, string>
     */
    public function grantedPermissions(): array
    {
        return $this->isSuperRole() ? Permission::values() : array_values($this->permissions);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }
}
