<?php

namespace Database\Factories;

use App\Enums\FrameUploadKind;
use App\Enums\FrameUploadStatus;
use App\Models\FrameUpload;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FrameUpload>
 */
class FrameUploadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'original_name' => fake()->word().'.jpg',
            'kind' => FrameUploadKind::Image,
            'status' => FrameUploadStatus::Queued,
            'progress' => 0,
            'message' => null,
            'stored_path' => 'frame-uploads/'.fake()->uuid(),
            'frame_name' => null,
            'finished_at' => null,
        ];
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes): array => [
            'original_name' => fake()->word().'.mov',
            'kind' => FrameUploadKind::Video,
        ]);
    }

    public function transcoding(int $progress = 40): static
    {
        return $this->state(fn (array $attributes): array => [
            'kind' => FrameUploadKind::Video,
            'status' => FrameUploadStatus::Transcoding,
            'progress' => $progress,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FrameUploadStatus::Completed,
            'progress' => 100,
            'stored_path' => null,
            'frame_name' => fake()->word().'.jpg',
            'finished_at' => now(),
        ]);
    }

    public function failed(string $message = 'рамка недоступна'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FrameUploadStatus::Failed,
            'message' => $message,
            'stored_path' => null,
            'finished_at' => now(),
        ]);
    }
}
