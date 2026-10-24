<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * 法人番号（13桁）の先頭にあるチェック用の数字が、残り12桁と合っているかを確かめる。
 *
 * 13桁の数字であることは、このルールの前に digits:13 で確かめておく。
 */
class CorporateNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/\A[0-9]{13}\z/', $value) !== 1) {
            return;
        }

        if ((int) $value[0] !== self::checkDigit(substr($value, 1))) {
            $fail('法人番号が正しくありません。番号をもう一度ご確認ください。');
        }
    }

    /**
     * 法人番号の後ろ12桁から、先頭のチェック用の数字を計算する（国税庁が定めた計算方法）。
     *
     * 右から数えて奇数番目の数字は1倍、偶数番目は2倍して足し、9で割った余りを9から引く。
     */
    public static function checkDigit(string $base): int
    {
        $sum = 0;

        foreach (array_reverse(str_split($base)) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 2);
        }

        return 9 - ($sum % 9);
    }
}
