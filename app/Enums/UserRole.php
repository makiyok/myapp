<?php

namespace App\Enums;

enum UserRole: string
{
    case Visitor = 'visitor';
    case Facility = 'facility';
    case Admin = 'admin';

    /**
     * 画面に表示する日本語のラベル
     */
    public function label(): string
    {
        return match ($this) {
            self::Visitor => '慰問者',
            self::Facility => '福祉施設',
            self::Admin => '管理者',
        };
    }
}
