@if (config('services.tawkto.enabled') && !empty(config('services.tawkto.property_id')) && !empty(config('services.tawkto.widget_id')))
    @if ( !config('cookie-consent.enabled') ||
          (config('cookie-consent.enabled') && \Illuminate\Support\Facades\Cookie::get(config('cookie-consent.cookie_name')) !== null)
    )
        <!--Start of Tawk.to Script-->
        <script type="text/javascript">
            var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();
            (function() {
                var s1 = document.createElement("script"), s0 = document.getElementsByTagName("script")[0];
                s1.async = true;
                s1.src = 'https://embed.tawk.to/{{ config('services.tawkto.property_id') }}/{{ config('services.tawkto.widget_id') }}';
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
            })();
        </script>
        <!--End of Tawk.to Script-->
    @endif
@endif

