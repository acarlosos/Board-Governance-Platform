@php
    $bgPath = config('bgp_login.pgtrust.brand_background');
    $pgTrustLogoPath = config('bgp_login.pgtrust.logo');
    $governanceLogoPath = config('bgp_login.governance2u.logo');
@endphp

<div class="bgp-login" data-bgp-login="pgtrust">
    <div class="bgp-login__outer">
        <div class="bgp-login__frame">
            <div class="bgp-login__grid">
                <section class="bgp-login__brand" aria-label="{{ __('login.pgtrust.brand_aria') }}">
                    @if (filled($bgPath))
                        <img class="bgp-login__brand-bg" src="{{ asset($bgPath) }}" alt="" loading="eager" decoding="async" />
                    @endif

                    <div class="bgp-login__brand-inner">
                        <header class="bgp-login__brand-head">
                            <div class="bgp-login__logo" aria-label="{{ __('login.pgtrust.logo_aria') }}">
                                @if (filled($pgTrustLogoPath))
                                    <img class="bgp-login__logo-img" src="{{ asset($pgTrustLogoPath) }}" alt="PG Trust" loading="eager" decoding="async" />
                                @else
                                    <span class="bgp-login__logo-mark" aria-hidden="true"></span>
                                    <span class="bgp-login__logo-text">pgtrust</span>
                                @endif
                            </div>
                        </header>

                        <div class="bgp-login__brand-main">
                            <div class="bgp-login__headline">
                                @if (filled($governanceLogoPath))
                                    <img class="bgp-login__product-logo" src="{{ asset($governanceLogoPath) }}" alt="{{ __('login.pgtrust.title') }}" loading="eager" decoding="async" />
                                @else
                                    <h1 class="bgp-login__title">{{ __('login.pgtrust.title') }}</h1>
                                @endif
                            </div>

                            <div class="bgp-login__divider" aria-hidden="true"></div>

                            <p class="bgp-login__copy">{{ __('login.pgtrust.copy') }}</p>
                        </div>

                        <div class="bgp-login__features" aria-label="{{ __('login.pgtrust.features_aria') }}">
                            <div class="bgp-login__feature">
                                <span class="bgp-login__feature-ic" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" class="bgp-login__ic">
                                        <path d="M12 2l8 4v6c0 5-3.4 9.4-8 10-4.6-.6-8-5-8-10V6l8-4z" stroke="currentColor" stroke-width="1.7" />
                                        <path d="M9.4 12.2l1.8 1.8 3.9-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <span class="bgp-login__feature-tx">{{ __('login.pgtrust.features.security') }}</span>
                            </div>
                            <div class="bgp-login__feature">
                                <span class="bgp-login__feature-ic" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" class="bgp-login__ic">
                                        <path d="M4 9h16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                                        <path d="M6 9V7.6c0-.9.7-1.6 1.6-1.6h8.8c.9 0 1.6.7 1.6 1.6V9" stroke="currentColor" stroke-width="1.7" />
                                        <path d="M6.5 9v9m3.5-9v9m4-9v9m3.5-9v9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                                        <path d="M4.5 18h15" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                                    </svg>
                                </span>
                                <span class="bgp-login__feature-tx">{{ __('login.pgtrust.features.governance') }}</span>
                            </div>
                            <div class="bgp-login__feature">
                                <span class="bgp-login__feature-ic" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" class="bgp-login__ic">
                                        <path d="M5 17l5-5 3 3 6-7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M5 7v10h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                                    </svg>
                                </span>
                                <span class="bgp-login__feature-tx">{{ __('login.pgtrust.features.efficiency') }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="bgp-login__form" aria-label="{{ __('login.pgtrust.form_aria') }}">
                    <div class="bgp-login__form-inner">
                        <div class="bgp-login__form-head">
                            <h2 class="bgp-login__form-title">{{ $this->getHeading() }}</h2>
                            <div class="bgp-login__form-subtitle">
                                @if (filament()->hasLogin())
                                    <a href="{{ filament()->getLoginUrl() }}">
                                        <span aria-hidden="true">←</span>
                                        {{ __('filament-panels::auth/pages/password-reset/request-password-reset.actions.login.label') }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="bgp-login__form-body">
                            {{ $this->content }}
                        </div>

                        <div class="bgp-login__copyright">
                            <span>© {{ now()->year }} PG Trust. {{ __('login.pgtrust.rights_reserved') }}</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

