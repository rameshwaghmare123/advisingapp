<x-filament::section aside>
    <x-slot name="heading">
        Two Factor Authentication
    </x-slot>

    <x-slot name="description">
        Manage multifactor authentication for your account
    </x-slot>

    @if($this->showRequiresTwoFactorAlert())

        <div style="{{ \Illuminate\Support\Arr::toCssStyles([\Filament\Support\get_color_css_variables('danger',shades: [300, 400, 500, 600])]) }}" class="p-4 rounded bg-custom-500">
            <div class="flex">
                <div class="flex-shrink-0">
                    @svg('heroicon-s-shield-exclamation', 'w-5 h-5 text-danger-600')
                </div>
                <div class="ml-3">
                    <p class="text-sm text-danger-500">
                        You must enable Two Factor Authentication to use this application.
                    </p>
                </div>
            </div>
        </div>
    @endif

    @unless ($user->hasEnabledTwoFactor())
        <h3 class="flex items-center gap-2 text-lg font-medium">
            @svg('heroicon-o-exclamation-circle', 'w-6')
            You have not enabled two factor authentication.
        </h3>
        <p class="text-sm">When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your Authenticator application.</p>

        <div class="flex justify-between mt-3">
            {{ $this->enableAction }}
        </div>

    @else
        @if ($user->hasConfirmedTwoFactor())
            <h3 class="flex items-center gap-2 text-lg font-medium">
                @svg('heroicon-o-shield-check', 'w-6')
                You have enabled two factor authentication!
            </h3>
            <p class="text-sm">Two factor authentication is now enabled. This helps make your account more secure.</p>
            @if($showRecoveryCodes)
                <div class="px-4 space-y-3">
                    <p class="text-xs">These codes can be used to recover access to your account if your device is lost. Warning! These codes will only be shown once.</p>
                    <div>
                        @foreach ($this->recoveryCodes->toArray() as $code )
                        <span class="inline-flex items-center p-1 text-xs font-medium text-gray-800 dark:text-gray-400 bg-gray-100 rounded-full dark:bg-gray-900">{{ $code }}</span>
                        @endforeach
                    </div>
                    <div class="inline-block text-xs">
                        <a
                            x-data="{}"
                            :x-on:click.prevent="window.navigator.clipboard.writeText(@js($this->recoveryCodes->join(',')));$tooltip('Copied!');"
                            href="#"
                            class="flex items-center"
                        >
                            @svg('heroicon-s-clipboard-document', 'w-4 mr-2')
                            <span class="">Copy to clipboard</span>
                        </a>
                    </div>
                </div>
            @endif
            <div class="flex justify-between mt-3">
                {{ $this->regenerateCodesAction }}
                {{ $this->disableAction()->color('danger') }}
            </div>
        @else
            <h3 class="flex items-center gap-2 text-lg font-medium">
                @svg('heroicon-o-question-mark-circle', 'w-6')
                Finish enabling two factor authentication.
            </h3>
            <p class="text-sm">To finish enabling two factor authentication, scan the following QR code using your phone's authenticator application or enter the setup key and provide the generated OTP code.</p>
            <div class="flex mt-3 space-x-4">
                <div>
                    {!! $this->getTwoFactorQrCode() !!}
                    <p class="pt-2 text-sm">Setup key {{
                        decrypt($this->user->two_factor_secret) }}</p>
                </div>
            </div>

            <div class="flex justify-between mt-3">
                {{ $this->confirmAction }}
                {{ $this->disableAction }}
            </div>

        @endif

    @endunless
    <x-filament-actions::modals />
</x-filament::section>