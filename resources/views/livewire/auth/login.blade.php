<div class="login-wrap">
    <div class="login-card">
        <div class="login-side">
            <div class="mark">HX</div>
            <div>
                <h1>Hexagro Shareholding &amp; Finance</h1>
                <p>One place to track spend, sales, banking and shareholder settlement — replacing the Excel workbook with a live, always-current view.</p>
            </div>
            <div class="login-foot-side">
                Four fixed accounts — preset access per shareholder.
            </div>
        </div>

        <div class="login-form">
            <h2>Sign in</h2>
            <div class="sub">Select your account to continue. Access is preset per person.</div>

            @error('login')
                <div class="login-error">{{ $message }}</div>
            @enderror

            <div class="user-grid">
                @foreach ($users as $user)
                    <button
                        type="button"
                        wire:click="loginAs({{ $user->id }})"
                        wire:loading.attr="disabled"
                        class="user-card {{ $user->role->value === 'VIEWER' ? 'viewer' : '' }}"
                    >
                        <div class="user-avatar">{{ $user->initials }}</div>
                        <b>{{ $user->name }}</b>
                        <span class="role-pill {{ strtolower($user->role->value) }}">
                            {{ $user->role->value === 'ADMIN' ? 'Admin' : 'Viewer' }}
                        </span>
                    </button>
                @endforeach
            </div>

            <div class="login-foot">
                Four fixed accounts — no self sign-up. Admin (Jagadeesan) can add, import and edit records; Viewers have full read access to every report.
            </div>
        </div>
    </div>
</div>
