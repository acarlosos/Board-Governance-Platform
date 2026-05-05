<?php

return [
    'navigation_group' => 'Admin',
    'navigation_label' => 'Integrações',
    'model_label' => 'Integração',
    'plural_label' => 'Integrações',

    'sections' => [
        'data' => 'Dados',
        'config' => 'Configuração',
        'test' => 'Teste',
        'organization' => 'Organização',
    ],

    'fields' => [
        'tenant' => 'Organização',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
        'name' => 'Nome',
        'type' => 'Tipo',
        'provider' => 'Provedor',
        'status' => 'Status',
        'last_tested_at' => 'Último teste em',
        'last_test_status' => 'Status do teste',
        'last_test_message' => 'Mensagem do teste',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'type' => [
        'email' => 'E-mail',
        'storage' => 'Storage',
        'signature' => 'Assinatura',
        'video_conference' => 'Videoconferência',
        'reporting' => 'Relatórios',
        'identity' => 'Identidade',
    ],

    'provider' => [
        'smtp' => 'SMTP',
        'microsoft_365' => 'Microsoft 365',
        'onedrive' => 'OneDrive',
        'docusign' => 'DocuSign',
        'teams' => 'Teams',
        'zoom' => 'Zoom',
        'looker_studio' => 'Looker Studio',
    ],

    'status' => [
        'inactive' => 'Inativa',
        'active' => 'Ativa',
        'error' => 'Erro',
    ],

    'actions' => [
        'test' => 'Testar conexão',
        'enable' => 'Ativar',
        'disable' => 'Desativar',
    ],

    'logs' => [
        'created' => 'Integração criada.',
        'updated' => 'Integração atualizada.',
        'enabled' => 'Integração ativada.',
        'disabled' => 'Integração desativada.',
    ],

    'helper' => [
        'keep_secret_if_empty' => 'Deixe em branco para manter o valor atual.',
    ],

    'validation' => [
        'tenant_required' => 'Tenant é obrigatório.',
        'tenant_mismatch' => 'Registro não pertence ao tenant atual.',
        'missing_required' => 'Campos obrigatórios ausentes',
        'enable_requires_successful_test' => 'Só é permitido ativar após um teste bem-sucedido.',
    ],

    'test' => [
        'ok' => 'Configuração válida (teste fake).',
        'missing_required' => 'Campos obrigatórios ausentes para o provedor.',
    ],

    'config' => [
        'host' => 'Host',
        'port' => 'Porta',
        'username' => 'Usuário',
        'password' => 'Senha',
        'encryption' => 'Criptografia',
        'from_address' => 'E-mail remetente',
        'from_name' => 'Nome remetente',
        'tenant_id' => 'Tenant ID',
        'client_id' => 'Client ID',
        'client_secret' => 'Client Secret',
        'redirect_uri' => 'Redirect URI',
        'root_folder' => 'Pasta raiz',
        'account_id' => 'Account ID',
        'integration_key' => 'Integration Key',
        'user_id' => 'User ID',
        'private_key' => 'Private Key',
        'base_uri' => 'Base URI',
        'report_url' => 'URL do relatório',
    ],
];

