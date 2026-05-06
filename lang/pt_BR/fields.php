<?php

/**
 * Etiquetas reutilizáveis de formulários e tabelas (domínio da app).
 * Uma chave por conceito: fields.{grupo}.{campo}.
 * Painel Filament (Fase 3): recursos usam `tenants.php`, `users.php`, `roles.php` (papéis Spatie) e `actions.php`.
 */
return [

    'tenant' => 'Organização',
    'created_at' => 'Criado em',
    'updated_at' => 'Atualizado em',

    'user' => [
        'model_label' => 'Usuário',
        'plural_label' => 'Usuários',
        'navigation_label' => 'Usuários',
        'section_profile' => 'Perfil',
        'name' => 'Nome',
        'email' => 'Endereço de e-mail',
        'locale' => 'Idioma da interface',
        'password' => 'Senha',
        'email_verified_at' => 'E-mail verificado em',
    ],

];
