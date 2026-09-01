<?php
namespace App\Notifications;

use App\Channels\FirebaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\FirebaseService;

class FirebasePushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $body;

    public function __construct($title, $body)
    {
        $this->title = $title;
        $this->body = $body;
    }

    public function via($notifiable)
    {
        $channels = ['database'];

        if ($notifiable->devices()->whereNotNull('device_token')->where('device_token', '!=', '')->exists()) {
            $channels[] = FirebaseChannel::class;
        }

        return $channels;
    }

    public function toFirebase($notifiable)
    {
        $deviceTokens = $notifiable->devices()
            ->pluck('device_token')
            ->toArray();

        if (!empty($deviceTokens)) {
            app(FirebaseService::class)
                ->sendNotificationToMultiple(
                    $deviceTokens,
                    $this->title,
                    $this->body
                );
        }
    }

    public function toArray($notifiable)
    {
        \Log::info('Saving notification to database', [
            'title' => $this->title,
            'body' => $this->body
        ]);

        return [
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}