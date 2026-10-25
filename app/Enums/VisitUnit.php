<?php

namespace App\Enums;

/**
 * 申し込みの単位。1人ずつ申し込む慰問と、漫才のコンビのように1組で申し込む慰問がある。
 */
enum VisitUnit: string
{
    case Person = 'person';
    case Group = 'group';

    /**
     * 数のうしろに付ける単位（「3人」「3組」）
     */
    public function label(): string
    {
        return match ($this) {
            self::Person => '人',
            self::Group => '組',
        };
    }

    /**
     * 表の見出しなどに使う呼び方（「人数」「組数」）
     */
    public function countLabel(): string
    {
        return $this->label().'数';
    }
}
