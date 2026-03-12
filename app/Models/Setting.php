<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'label',
        'description',
    ];

    public static function get(string $key, $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): static
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

}
