<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('tenant.delivery-ops.{tenantId}', function ($user, $tenantId) {
    if ((int) ($user->tenant_id ?? 0) !== (int) $tenantId) {
        return false;
    }

    return $user->hasStoreRole('owner', 'admin', 'seller', 'warehouse', 'delivery');
});
