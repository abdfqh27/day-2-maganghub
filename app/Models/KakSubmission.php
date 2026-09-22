<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KakSubmission extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kak_submissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'judul',
        'data',
        'status',
        'output_format',
        'generated_file_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Helper to get safe title.
     */
    public function getDisplayJudulAttribute(): string
    {
        if (!empty($this->judul)) {
            return $this->judul;
        }

        $data = $this->data ?? [];
        $asdep = $data['field_001'] ?? '';
        $tahun = $data['field_002'] ?? '';
        $topik = $data['field_003'] ?? '';

        if ($asdep || $tahun || $topik) {
            $parts = array_filter([$asdep ? "Asdep {$asdep}" : null, $tahun ? "Tahun {$tahun}" : null, $topik ? "- {$topik}" : null]);
            return 'KAK ' . implode(' ', $parts);
        }

        return "Pengajuan KAK #{$this->id}";
    }

    /**
     * Helper to check if generated file exists on disk.
     */
    public function hasGeneratedFile(): bool
    {
        if (!$this->generated_file_path) {
            return false;
        }
        return file_exists(storage_path('app/' . $this->generated_file_path)) || file_exists($this->generated_file_path);
    }

    /**
     * Get absolute path to generated file.
     */
    public function getAbsoluteFilePath(): ?string
    {
        if (!$this->generated_file_path) {
            return null;
        }

        if (file_exists($this->generated_file_path)) {
            return $this->generated_file_path;
        }

        $storagePath = storage_path('app/' . $this->generated_file_path);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        return null;
    }
}
