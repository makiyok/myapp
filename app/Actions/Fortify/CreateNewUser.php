<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use App\Rules\CorporateNumber;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'role' => ['required', Rule::in([UserRole::Visitor->value, UserRole::Facility->value])],
            // 介護保険事業所番号。福祉施設のときだけ確認し、それ以外では入力されていても捨てる
            'facility_number' => ['exclude_unless:role,'.UserRole::Facility->value, ...$this->facilityNumberRules()],
            'corporate_number' => ['exclude_unless:role,'.UserRole::Facility->value, ...$this->corporateNumberRules()],
        ])->validate();

        $user = new User([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
        $user->role = UserRole::from($input['role']);
        $user->facility_number = $user->isFacility() ? $input['facility_number'] : null;
        $user->corporate_number = $user->isFacility() ? $input['corporate_number'] : null;
        $user->save();

        return $user;
    }

    /**
     * 介護保険事業所番号のルール。本番では数字10桁に限る。
     * 開発環境では動作確認やデモのため形式を問わず、カラムに入る長さだけ確かめる。
     *
     * @return array<int, string>
     */
    private function facilityNumberRules(): array
    {
        return app()->isProduction()
            ? ['required', 'string', 'digits:10']
            : ['required', 'string', 'max:10'];
    }

    /**
     * 法人番号のルール。本番では数字13桁で、チェック用の数字も合っていること。
     * 開発環境では動作確認やデモのため形式を問わず、カラムに入る長さだけ確かめる。
     *
     * @return array<int, CorporateNumber|string>
     */
    private function corporateNumberRules(): array
    {
        return app()->isProduction()
            ? ['required', 'string', 'digits:13', new CorporateNumber]
            : ['required', 'string', 'max:13'];
    }
}
