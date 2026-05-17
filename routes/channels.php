<?php

use App\Models\AgentTask;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
| Каналы для системы агентов:
|   private-agent.{fid}               — все события агентов для проекта (fid)
|   private-agent.{fid}.{agent_name}  — события для конкретного агента
|
| Авторизация: fid должен совпадать с fid пользователя (из сессии или токена).
|
*/

/**
 * Канал всех агентов для проекта — слушают все участники.
 * Доступ: любой авторизованный пользователь с совпадающим fid.
 */
Broadcast::channel('agent.{fid}', function ($user, int $fid) {
    $userFid = (int) ($user->fid ?? session('fid', 0));

    $authorized = $userFid === $fid;

    if (!$authorized) {
        Log::warning('channels:agent.fid — access denied', [
            'user_id'  => $user->id ?? null,
            'user_fid' => $userFid,
            'fid'      => $fid,
        ]);
    }

    return $authorized ? ['id' => $user->id, 'name' => $user->name ?? ''] : false;
});

/**
 * Канал конкретного агента — для точечных уведомлений.
 * Доступ: авторизованный пользователь с совпадающим fid.
 */
Broadcast::channel('agent.{fid}.{agentName}', function ($user, int $fid, string $agentName) {
    $userFid = (int) ($user->fid ?? session('fid', 0));

    $authorized = $userFid === $fid
        && in_array($agentName, ['backend', 'telegram', 'frontend', 'system'], true);

    if (!$authorized) {
        Log::warning('channels:agent.fid.agentName — access denied', [
            'user_id'    => $user->id ?? null,
            'user_fid'   => $userFid,
            'fid'        => $fid,
            'agent_name' => $agentName,
        ]);
    }

    return $authorized ? ['id' => $user->id, 'name' => $user->name ?? ''] : false;
});
