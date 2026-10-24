<x-layouts::auth title="会員登録">
    <div class="flex flex-col gap-6">
        <x-auth-header title="アカウントを作成" description="アカウント作成に必要な情報を入力してください" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                label="名前"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="氏名"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                label="メールアドレス"
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
                label="パスワード"
                type="password"
                required
                autocomplete="new-password"
                placeholder="パスワード"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                label="パスワードの確認"
                type="password"
                required
                autocomplete="new-password"
                placeholder="パスワードの確認"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    アカウントを作成
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>すでにアカウントをお持ちですか？</span>
            <flux:link :href="route('login')" wire:navigate>ログイン</flux:link>
        </div>
    </div>
</x-layouts::auth>
