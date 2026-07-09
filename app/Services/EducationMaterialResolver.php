<?php

namespace App\Services;

use App\Models\EducationalMaterial;

class EducationMaterialResolver
{
    private const LEVELS = ['beginner', 'intermediate', 'advanced'];

    public function initial(int $topicId, string $level = 'beginner'): ?EducationalMaterial
    {
        return $this->latestFor($topicId, $this->normalizeLevel($level));
    }

    public function afterFailure(EducationalMaterial $current): ?EducationalMaterial
    {
        $alternative = EducationalMaterial::query()
            ->where('topic_id', $current->topic_id)
            ->where('level', $current->level)
            ->where('is_active', true)
            ->whereRaw('CAST(version AS DECIMAL(10,2)) > CAST(? AS DECIMAL(10,2))', [$current->version])
            ->orderByRaw('CAST(version AS DECIMAL(10,2)) ASC')
            ->first();

        if ($alternative) {
            return $alternative;
        }

        $index = array_search($current->level, self::LEVELS, true);
        $lowerLevel = $index !== false && $index > 0 ? self::LEVELS[$index - 1] : $current->level;

        return $this->latestFor((int) $current->topic_id, $lowerLevel) ?? $current;
    }

    public function nextLevel(EducationalMaterial $current): ?EducationalMaterial
    {
        $index = array_search($current->level, self::LEVELS, true);
        $nextLevel = $index !== false && $index < count(self::LEVELS) - 1
            ? self::LEVELS[$index + 1]
            : $current->level;

        return $this->latestFor((int) $current->topic_id, $nextLevel) ?? $current;
    }

    private function latestFor(int $topicId, string $level): ?EducationalMaterial
    {
        return EducationalMaterial::query()
            ->where('topic_id', $topicId)
            ->where('level', $level)
            ->where('is_active', true)
            ->orderByRaw('CAST(version AS DECIMAL(10,2)) DESC')
            ->first();
    }

    private function normalizeLevel(string $level): string
    {
        return in_array($level, self::LEVELS, true) ? $level : 'beginner';
    }
}
