<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Role -->
            <fieldset class="grid gap-2">
                <legend class="mb-2 text-sm font-medium text-zinc-800 dark:text-white">登録の種類</legend>

                <div class="flex flex-wrap gap-3">
                    @foreach ([\App\Enums\UserRole::Visitor, \App\Enums\UserRole::Facility] as $role)
                        <label class="flex cursor-pointer items-center gap-2 rounded-2xl border-2 border-zinc-200 px-4 py-2 text-zinc-800 has-checked:border-accent has-checked:bg-zinc-50 dark:text-white dark:has-checked:bg-white/10">
                            <input
                                type="radio"
                                name="role"
                                value="{{ $role->value }}"
                                class="size-4 accent-accent"
                                @checked(old('role', \App\Enums\UserRole::Visitor->value) === $role->value)
                            />
                            <span>{{ $role->label() }}</span>
                        </label>
                    @endforeach
                </div>

                <flux:error name="role" />
            </fieldset>

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
