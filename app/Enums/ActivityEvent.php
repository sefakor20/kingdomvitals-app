<?php

namespace App\Enums;

enum ActivityEvent: string
{
    // Model CRUD events
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';

    // Authentication events
    case Login = 'login';
    case Logout = 'logout';
    case FailedLogin = 'failed_login';

    // Bulk/Export events
    case Exported = 'exported';
    case Imported = 'imported';
    case BulkUpdated = 'bulk_updated';
    case BulkDeleted = 'bulk_deleted';

    public function category(): string
    {
        return match ($this) {
            self::Created, self::Updated, self::Deleted, self::Restored => 'model',
            self::Login, self::Logout, self::FailedLogin => 'auth',
            self::Exported, self::Imported, self::BulkUpdated, self::BulkDeleted => 'bulk',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Updated => 'Updated',
            self::Deleted => 'Deleted',
            self::Restored => 'Restored',
            self::Login => 'Logged In',
            self::Logout => 'Logged Out',
            self::FailedLogin => 'Failed Login',
            self::Exported => 'Exported',
            self::Imported => 'Imported',
            self::BulkUpdated => 'Bulk Updated',
            self::BulkDeleted => 'Bulk Deleted',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Created => 'plus',
            self::Updated => 'pencil',
            self::Deleted => 'trash',
            self::Restored => 'arrow-path',
            self::Login => 'arrow-right-end-on-rectangle',
            self::Logout => 'arrow-left-start-on-rectangle',
            self::FailedLogin => 'exclamation-triangle',
            self::Exported => 'arrow-down-tray',
            self::Imported => 'arrow-up-tray',
            self::BulkUpdated => 'pencil-square',
            self::BulkDeleted => 'trash',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Created => 'green',
            self::Updated => 'blue',
            self::Deleted => 'red',
            self::Restored => 'yellow',
            self::Login => 'emerald',
            self::Logout => 'slate',
            self::FailedLogin => 'red',
            self::Exported => 'indigo',
            self::Imported => 'purple',
            self::BulkUpdated => 'blue',
            self::BulkDeleted => 'red',
        };
    }
}
