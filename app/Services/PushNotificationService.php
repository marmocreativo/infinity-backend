<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class PushNotificationService
{
    public function __construct(private Messaging $messaging)
    {
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $user->deviceTokens()->pluck('token');

        if ($tokens->isEmpty()) {
            return;
        }

        foreach ($tokens as $token) {
            try {
                $message = CloudMessage::new()
                    ->withNotification(FirebaseNotification::create($title, $body))
                    ->withData($data)
                    ->toToken($token);

                $this->messaging->send($message);
            } catch (\Throwable $e) {
                logger()->warning("Push notification failed for token {$token}: " . $e->getMessage());
            }
        }
    }

    public function sendToRole(string $role, string $title, string $body, array $data = []): void
    {
        User::role($role)->get()->each(function (User $user) use ($title, $body, $data) {
            $this->sendToUser($user, $title, $body, $data);
        });
    }
}
