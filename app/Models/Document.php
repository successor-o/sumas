<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $fillable = [
        'user_id',
        'doc_type',
        'original_name',
        'stored_name',
        'mime',
        'size',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Public URL for the stored file (requires php artisan storage:link).
     */
    public function url(): string
    {
        return Storage::disk('public')->url("documents/{$this->user_id}/{$this->stored_name}");
    }

    /**
     * Human-readable label for the document type.
     */
    public function label(): string
    {
        return [
            'school-id'  => 'School ID Card',
            'admission'  => 'Admission Letter',
            'clearance'  => 'Department Clearance',
            'nat-id'     => 'National ID Card',
            'pp-1'       => 'Passport Photo 1',
            'pp-2'       => 'Passport Photo 2',
            'pp-3'       => 'Passport Photo 3',
        ][$this->doc_type] ?? ucfirst(str_replace('-', ' ', $this->doc_type));
    }
}
