# Documents (documentos)

## Objetivo

Gestão de documentos **com upload privado**, **storage separado por tenant**, **versionamento** e **logs de acesso** (visualização / download), com auditoria sem expor paths sensíveis.

## Tabelas envolvidas

- `documents`
- `document_versions`
- `document_access_logs`

## Models envolvidos

- `App\Models\Document`
- `App\Models\DocumentVersion`
- `App\Models\DocumentAccessLog`

## Policies envolvidas

- `App\Policies\DocumentPolicy`
- `App\Policies\DocumentVersionPolicy`
- `App\Policies\DocumentAccessLogPolicy`

## Services / Actions envolvidos

- `App\Actions\Documents\PersistDocumentAction`
- `App\Actions\Documents\UploadDocumentVersionAction`
- `App\Actions\Documents\PublishDocumentAction`
- `App\Actions\Documents\ArchiveDocumentAction`
- `App\Actions\Documents\RecordDocumentAccessAction`
- Auditoria: `App\Services\Audit\AuditLoggerService` (via observers)

## Regras de negócio

- Documento pode ter contexto opcional: `board_id` e/ou `meeting_id`.
- `meeting_id` e `board_id` **devem pertencer ao mesmo tenant** do documento.
- Versionamento é incremental (`version_number` crescente por `document_id`).
- `current_version_id` aponta sempre para a última versão enviada.

## Regras de segurança

- **Upload privado** (não público).
- **Storage separado por tenant** em `private/tenants/{tenant_id}/documents/{document_id}/versions/{n}/...`.
- **Não confiar no nome original**: ficheiro é armazenado com nome aleatório (UUID), preservando `original_name` apenas como metadado.
- **Validação no backend**: extensão (whitelist em `config/board.php`) e tamanho máximo (`max_upload_size_kb`).
- **Anti-vazamento de tenant**:
  - `PersistDocumentAction` valida coerência de tenant ao vincular `board_id` / `meeting_id`.
  - `UploadDocumentVersionAction` valida tenant do documento.
  - Policies + `DocumentResource::getEloquentQuery()` garantem listagens/leituras seguras.
- **Auditoria sem path sensível**: `DocumentVersionObserver` não registra `file_path` em `audit_logs`.
- **Download seguro**: endpoint autenticado aplica `DocumentPolicy::view`, registra `DocumentAccessLog` e suporta streaming para arquivos grandes.

## Testes relacionados

- `tests/Feature/DocumentsTest.php`
  - bloqueio de vínculo cross-tenant (board/meeting)
  - upload privado com path tenant-safe, nome randomizado, versão incremental
  - validação de extensão
  - bloqueio de upload em documento de outro tenant
  - logs de acesso (view/download)
  - auditoria sem expor `file_path`
  - endpoint `/documents/{document}/download` (200/403/404 + mensagem genérica sem path)

## Pendências futuras

- UI de download “open/preview” com temporary URL em drivers compatíveis.
- Regras adicionais de acesso por `DocumentStatus` (ex.: restringir `draft`).
