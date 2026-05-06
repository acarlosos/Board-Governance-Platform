<?php

return [
    'navigation_label' => 'Segurança',
    'navigation_group' => 'Conta',
    'title' => 'Segurança',
    'sections' => [
        'two_factor' => 'Autenticação de dois fatores',
        'sessions' => 'Sessões ativas',
        'password' => 'Trocar senha',
    ],
    'descriptions' => [
        'two_factor' => 'Use um aplicativo autenticador (Google Authenticator, 1Password, Authy) para reforçar o login.',
        'sessions' => 'Lista de sessões abertas. Encerre uma sessão para desconectá-la imediatamente.',
        'password' => 'Sua senha deve ter no mínimo 8 caracteres, com letras maiúsculas, minúsculas, números e símbolos.',
    ],
    'fields' => [
        'session_id' => 'Sessão',
        'user' => 'Usuário',
        'tenant' => 'Organização',
        'ip_address' => 'IP',
        'user_agent' => 'Navegador',
        'login_at' => 'Iniciada em',
        'last_activity_at' => 'Última atividade',
        'status' => 'Status',
    ],
    'status' => [
        'active' => 'Ativa',
        'closed' => 'Encerrada',
        'expired' => 'Expirada',
    ],
    'actions' => [
        'revoke' => 'Encerrar sessão',
        'revoke_confirm_heading' => 'Encerrar sessão?',
        'revoke_confirm_description' => 'A sessão será desconectada imediatamente.',
        'update_password' => 'Atualizar senha',
    ],
    'sessions' => [
        'rate_limited' => 'Muitas tentativas de encerrar sessões. Tente novamente em alguns instantes.',
        'unauthorized' => 'Você não pode encerrar esta sessão.',
        'not_found' => 'Sessão inválida.',
        'revoked' => 'Sessão encerrada.',
        'empty' => 'Nenhuma sessão ativa registrada.',
        'empty_heading' => 'Nenhuma sessão ativa',
        'empty_description' => 'Quando houver sessões ativas, elas aparecerão aqui para você encerrar rapidamente.',
    ],
    'password' => [
        'rate_limited' => 'Muitas tentativas de troca de senha. Tente novamente em alguns instantes.',
        'invalid_current' => 'Senha atual incorreta.',
        'updated' => 'Senha atualizada com sucesso.',
        'attributes' => [
            'current_password' => 'senha atual',
            'password' => 'nova senha',
            'password_confirmation' => 'confirmar nova senha',
        ],
    ],
];
