<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * In-app notification stored in the database. The text is kept as a translation
 * key plus parameters, so it renders in whatever language the reader uses.
 */
class PortalNotification extends Notification
{
    public function __construct(
        public string $key,
        public array $params = [],
        public ?string $url = null,
        public string $icon = 'bell',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'key' => $this->key,
            'params' => $this->params,
            'url' => $this->url,
            'icon' => $this->icon,
        ];
    }

    /** Render a stored notification's text in the current locale. */
    public static function text(array $data): string
    {
        return __($data['key'] ?? '', $data['params'] ?? []);
    }
}
