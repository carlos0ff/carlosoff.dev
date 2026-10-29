<?php

namespace App\Services;

use App\Enums\StaffRole;
use App\Models\Client;
use App\Models\User;
use App\Notifications\ClientMessage;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Notification;

/**
 * Ponto único de envio de avisos: usuários do portal do cliente + e-mail de cobrança, e alertas para a equipe.
 */
class Notifier
{
    /**
     * @param  list<string>  $lines
     */
    public function client(Client $client, string $subject, array $lines, ?string $actionText = null, ?string $actionUrl = null, string $category = 'geral', bool $billing = false): string
    {
        $notification = new ClientMessage($subject, $lines, $actionText, $actionUrl, $category);
        $portalUsers = $client->portalUsers()->get();

        Notification::send($portalUsers, $notification);

        $recipient = $billing ? $client->billingRecipient() : $client->email;
        if ($recipient && ! $portalUsers->contains('email', $recipient)) {
            Notification::route('mail', $recipient)->notify($notification);
        }

        return $recipient;
    }

    /**
     * @param  list<StaffRole>  $roles
     */
    public function staff(string $subject, string $body, ?string $url = null, string $category = 'geral', array $roles = [StaffRole::Admin]): void
    {
        $users = User::query()
            ->where('is_admin', true)
            ->whereIn('role', array_map(fn (StaffRole $role) => $role->value, $roles))
            ->get();

        Notification::send($users, new StaffAlert($subject, $body, $url, $category));
    }
}
