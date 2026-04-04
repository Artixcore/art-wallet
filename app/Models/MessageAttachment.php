<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    public const UploadPending = 'pending';

    public const UploadComplete = 'complete';

    public const UploadFailed = 'failed';

    protected $fillable = [
        'message_id',
        'storage_path',
        'size_bytes',
        'content_type',
        'mime_hint',
        'crypto_meta',
        'enc_manifest',
        'ciphertext_sha256',
        'upload_state',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'crypto_meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Message, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
