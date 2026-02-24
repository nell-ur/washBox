<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'key',
        'type',
        'title',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function render(array $data): array
    {
        $title = $this->title;
        $body = $this->body;

        foreach ($data as $key => $value) {
            $title = str_replace("{{{$key}}}", $value, $title);
            $body = str_replace("{{{$key}}}", $value, $body);
        }

        return [
            'title' => $title,
            'body' => $body,
        ];
    }
}
