<?php

namespace App\Models;

use App\UserRole;
use App\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Auth\User as Authenticatable;

#[Fillable(['name', 'first_name', 'last_name', 'email', 'password', 'avatar_path', 'student_code', 'role', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $connection = 'mongodb';

    protected $table = 'users';

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->role === UserRole::Student && blank($user->student_code)) {
                $user->student_code = static::nextStudentCode();
            }
        });
    }

    public static function nextStudentCode(): string
    {
        $highestNumber = (int) static::query()
            ->where('role', UserRole::Student->value)
            ->get(['student_code'])
            ->map(fn (User $student): int => self::studentCodeNumber($student->student_code))
            ->max();

        do {
            $studentCode = sprintf('EST-%03d', ++$highestNumber);
        } while (static::query()->where('student_code', $studentCode)->exists());

        return $studentCode;
    }

    private static function studentCodeNumber(?string $studentCode): int
    {
        if (preg_match('/^EST-(\d+)$/', trim((string) $studentCode), $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function canManageLibrary(): bool
    {
        return in_array($this->role, [UserRole::Librarian, UserRole::Admin], true);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

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
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }
}
