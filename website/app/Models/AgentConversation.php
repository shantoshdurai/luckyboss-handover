<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentConversation extends Model
{
    protected $fillable = ['user_id', 'intent', 'answers', 'status'];

    protected $casts = ['answers' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgentMessage::class)->orderBy('id');
    }

    /**
     * What the History rail shows for this conversation.
     *
     * A hiring conversation is named after the role it was about, because an
     * employer runs several and "Hiring" four times over is a list you cannot
     * navigate. TickBig's rail has exactly that problem — every row on it reads
     * "AI Or ML Engineer" whether or not that is what the conversation was.
     */
    public function title(): string
    {
        if ($this->intent === 'hire') {
            $answers = $this->answers ?? [];
            $role = trim((string) ($answers['role'] ?? ''));
            $where = trim((string) ($answers['location'] ?? ''));

            if ($role === '') {
                return 'Hiring';
            }

            return $where === '' ? $role : "{$role} in {$where}";
        }

        return match ($this->intent) {
            'find_job' => 'Finding work that fits',
            default => 'Lucky AI',
        };
    }

    public function say(string $role, string $body, ?string $questionKey = null): AgentMessage
    {
        return $this->messages()->create([
            'role' => $role,
            'body' => $body,
            'question_key' => $questionKey,
        ]);
    }
}
