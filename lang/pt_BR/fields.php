<?php

/**
 * Etiquetas reutilizáveis de formulários e tabelas (domínio da app).
 * Uma chave por conceito: fields.{grupo}.{campo}.
 * Painel Filament (Fase 3): recursos usam sobretudo `tenants.php`, `users.php` e `actions.php`.
 */
return [

    'user' => [
        'model_label' => 'Utilizador',
        'plural_label' => 'Utilizadores',
        'navigation_label' => 'Utilizadores',
        'section_profile' => 'Perfil',
        'name' => 'Nome',
        'email' => 'Endereço de e-mail',
        'locale' => 'Idioma da interface',
        'password' => 'Palavra-passe',
        'email_verified_at' => 'E-mail verificado em',
    ],

];
