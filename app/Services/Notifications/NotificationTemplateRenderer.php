<?php

namespace App\Services\Notifications;

use App\Models\NotificationTemplate;

final class NotificationTemplateRenderer
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{subject: string, body: string}
     */
    public function render(NotificationTemplate $template, array $data): array
    {
        $subject = $template->subject ?? '';
        $body = (string) $template->body;

        $subject = $this->replace($subject, $data);
        $body = $this->replace($body, $data);

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Substitui tokens simples do tipo {{ var_name }} por string.
     * - sem execução de código
     * - variáveis ausentes viram string vazia
     *
     * @param  array<string, mixed>  $data
     */
    private function replace(string $text, array $data): string
    {
        return (string) preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function (array $m) use ($data): string {
            $key = (string) ($m[1] ?? '');
            $value = $data[$key] ?? '';

            if (is_bool($value)) {
                return $value ? '1' : '0';
            }

            if (is_scalar($value)) {
                return (string) $value;
            }

            return '';
        }, $text);
    }
}

