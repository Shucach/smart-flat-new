<?php

namespace App\Models;

use App\Enums\FrameUploadKind;
use App\Enums\FrameUploadStatus;
use Database\Factories\FrameUploadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $original_name
 * @property FrameUploadKind $kind
 * @property FrameUploadStatus $status
 * @property int $progress
 * @property string|null $message
 * @property string|null $stored_path
 * @property string|null $frame_name
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'original_name', 'kind', 'status', 'progress', 'message', 'stored_path', 'frame_name'])]
class FrameUpload extends Model
{
    /** @use HasFactory<FrameUploadFactory> */
    use HasFactory;

    /**
     * How long a finished upload keeps being reported to the page.
     */
    public const int VISIBLE_MINUTES = 10;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Everything still in flight, plus what finished recently enough to be worth
     * showing: the outcome of an upload is the whole point of the panel.
     *
     * @param  Builder<FrameUpload>  $query
     * @return Builder<FrameUpload>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->whereNull('finished_at')
            ->orWhere('finished_at', '>=', now()->subMinutes(self::VISIBLE_MINUTES)));
    }

    public function markStatus(FrameUploadStatus $status, int $progress): void
    {
        $this->forceFill([
            'status' => $status,
            'progress' => max(0, min(100, $progress)),
        ])->save();
    }

    public function markCompleted(string $frameName): void
    {
        $this->forceFill([
            'status' => FrameUploadStatus::Completed,
            'progress' => 100,
            'frame_name' => $frameName,
            'message' => null,
            'stored_path' => null,
            'finished_at' => now(),
        ])->save();
    }

    public function markFailed(string $message): void
    {
        $this->forceFill([
            'status' => FrameUploadStatus::Failed,
            'message' => $message,
            'stored_path' => null,
            'finished_at' => now(),
        ])->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => FrameUploadKind::class,
            'status' => FrameUploadStatus::class,
            'progress' => 'integer',
            'finished_at' => 'datetime',
        ];
    }
}
