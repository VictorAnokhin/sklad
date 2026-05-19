<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AiTool extends Model
{
    protected $table = 'ai_tools';

    protected $fillable = [
        'fid',
        'key',
        'name',
        'description',
        'schema',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'fid' => 'integer',
        'schema' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Получить активные инструменты для проекта (fid) или глобальные (fid = null).
     *
     * @param  int|null  $fid  ID проекта. Если null — возвращаются только глобальные.
     * @return Collection<int, AiTool>
     */
    public static function getActive(?int $fid = null): Collection
    {
        return self::forFid($fid)
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Получить все инструменты для проекта (fid) или глобальные (fid = null).
     *
     * @param  int|null  $fid  ID проекта. Если null — возвращаются только глобальные.
     * @return Collection<int, AiTool>
     */
    public static function getAllForFid(?int $fid = null): Collection
    {
        return self::forFid($fid)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Scope: фильтр по fid.
     * Если fid = null — возвращаются только глобальные инструменты (fid IS NULL).
     * Если fid задан — возвращаются инструменты этого проекта (fid = ...).
     */
    public function scopeForFid($query, ?int $fid)
    {
        if ($fid === null) {
            return $query->whereNull('fid');
        }

        return $query->where(function ($q) use ($fid) {
            $q->where('fid', $fid)
              ->orWhereNull('fid');
        });
    }

    /**
     * Получить отформатированный список инструментов для system prompt AI.
     * Возвращает массив в формате OpenAI function calling.
     *
     * @param  int|null  $fid
     * @return array
     */
    public static function getToolsForPrompt(?int $fid = null): array
    {
        $tools = self::getActive($fid);

        return $tools->map(function (AiTool $tool) {
            return [
                'type' => 'function',
                'function' => [
                    'name' => $tool->key,
                    'description' => $tool->description ?? $tool->name,
                    'parameters' => self::normalizeJsonSchema($tool->schema),
                ],
            ];
        })->values()->toArray();
    }

    /**
     * DeepSeek/OpenAI expect function parameters to be a JSON Schema object.
     * Admin UI can save an empty schema as [] in JSON, so normalize that shape.
     *
     * @param  mixed  $schema
     * @return array<string, mixed>
     */
    private static function normalizeJsonSchema(mixed $schema): array
    {
        if (! is_array($schema) || $schema === []) {
            return [
                'type' => 'object',
                'properties' => (object) [],
                'required' => [],
            ];
        }

        $schema['type'] = $schema['type'] ?? 'object';
        $schema['properties'] = isset($schema['properties']) && is_array($schema['properties'])
            ? (object) $schema['properties']
            : ($schema['properties'] ?? (object) []);
        $schema['required'] = isset($schema['required']) && is_array($schema['required'])
            ? array_values($schema['required'])
            : [];

        return $schema;
    }
}
