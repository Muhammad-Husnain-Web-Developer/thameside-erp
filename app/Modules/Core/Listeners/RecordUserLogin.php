<?php

namespace App\Modules\Core\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

class RecordUserLogin
{
    /**
     * Stamp the sign-in time and keep an audit trail of who accessed the ERP.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => Request::ip(),
        ])->saveQuietly();

        activity('auth')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['ip' => Request::ip()])
            ->log('Signed in');
    }
}
