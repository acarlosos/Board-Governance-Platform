<?php

namespace Database\Seeders;

use App\Enums\NotificationChannel;
use App\Enums\NotificationTemplateStatus;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // key => [name, subject, body]
            'task_assigned' => [
                'name' => [
                    'pt_BR' => 'Tarefa atribuída',
                    'en' => 'Task assigned',
                    'es' => 'Tarea asignada',
                ],
                'subject' => [
                    'pt_BR' => 'Nova pendência: {{ task_title }}',
                    'en' => 'New task: {{ task_title }}',
                    'es' => 'Nueva tarea: {{ task_title }}',
                ],
                'body' => [
                    'pt_BR' => 'Olá {{ user_name }}, você recebeu a pendência "{{ task_title }}".',
                    'en' => 'Hi {{ user_name }}, you were assigned the task "{{ task_title }}".',
                    'es' => 'Hola {{ user_name }}, se te asignó la tarea "{{ task_title }}".',
                ],
                'variables' => ['user_name' => 'Nome do usuário', 'task_title' => 'Título da pendência'],
            ],
            'signature_requested' => [
                'name' => [
                    'pt_BR' => 'Assinatura solicitada',
                    'en' => 'Signature requested',
                    'es' => 'Firma solicitada',
                ],
                'subject' => [
                    'pt_BR' => 'Assinatura solicitada: {{ signature_title }}',
                    'en' => 'Signature requested: {{ signature_title }}',
                    'es' => 'Firma solicitada: {{ signature_title }}',
                ],
                'body' => [
                    'pt_BR' => 'Você tem uma solicitação de assinatura pendente: "{{ signature_title }}".',
                    'en' => 'You have a pending signature request: "{{ signature_title }}".',
                    'es' => 'Tienes una solicitud de firma pendiente: "{{ signature_title }}".',
                ],
                'variables' => ['signature_title' => 'Título da solicitação'],
            ],
            'minute_review_requested' => [
                'name' => [
                    'pt_BR' => 'Revisão de ata solicitada',
                    'en' => 'Minute review requested',
                    'es' => 'Revisión de acta solicitada',
                ],
                'subject' => [
                    'pt_BR' => 'Revisão de ata: {{ minute_title }}',
                    'en' => 'Minute review: {{ minute_title }}',
                    'es' => 'Revisión de acta: {{ minute_title }}',
                ],
                'body' => [
                    'pt_BR' => 'Uma ata está aguardando sua revisão: "{{ minute_title }}".',
                    'en' => 'A minute is awaiting your review: "{{ minute_title }}".',
                    'es' => 'Un acta está esperando tu revisión: "{{ minute_title }}".',
                ],
                'variables' => ['minute_title' => 'Título da ata'],
            ],
            'vote_opened' => [
                'name' => [
                    'pt_BR' => 'Votação aberta',
                    'en' => 'Vote opened',
                    'es' => 'Votación abierta',
                ],
                'subject' => [
                    'pt_BR' => 'Votação aberta: {{ vote_title }}',
                    'en' => 'Vote opened: {{ vote_title }}',
                    'es' => 'Votación abierta: {{ vote_title }}',
                ],
                'body' => [
                    'pt_BR' => 'Uma votação foi aberta: "{{ vote_title }}".',
                    'en' => 'A vote was opened: "{{ vote_title }}".',
                    'es' => 'Se abrió una votación: "{{ vote_title }}".',
                ],
                'variables' => ['vote_title' => 'Título da votação'],
            ],
        ];

        foreach ($templates as $key => $payload) {
            foreach (['pt_BR', 'en', 'es'] as $locale) {
                NotificationTemplate::query()->updateOrCreate(
                    [
                        'tenant_id' => null,
                        'key' => $key,
                        'locale' => $locale,
                        'channel' => NotificationChannel::Database->value,
                    ],
                    [
                        'name' => $payload['name'][$locale] ?? (string) $key,
                        'subject' => $payload['subject'][$locale] ?? null,
                        'body' => $payload['body'][$locale] ?? '',
                        'status' => NotificationTemplateStatus::Active->value,
                        'variables' => $payload['variables'] ?? null,
                        'created_by' => null,
                    ],
                );
            }
        }
    }
}

