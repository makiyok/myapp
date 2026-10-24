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
            'facility_number' => ['exclude_unless:role,'.UserRole::Facility->value, 'required', 'string', 'digits:10'],
            'corporate_number' => ['exclude_unless:role,'.UserRole::Facility->value, 'required', 'string', 'digits:13', new CorporateNumber],
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
}
