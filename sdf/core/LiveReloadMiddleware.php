<?php

namespace SDF\Middleware;

use SDF\Middleware;
use SDF\Request;

/**
 * Class LiveReloadMiddleware
 * Injects a live reload script into the response during development.
 */
class LiveReloadMiddleware implements Middleware
{
    public function handle(Request $request, \Closure $next): mixed
    {
        $response = $next($request);

        if (getenv('SDF_LIVE_RELOAD') === 'true' && str_contains($request->header('Accept') ?? '', 'text/html')) {
            $script = $this->getReloadScript();
            if (is_string($response)) {
                $response = str_replace('</body>', $script . '</body>', $response);
            }
        }

        return $response;
    }

    private function getReloadScript(): string
    {
        return <<<JS
<script>
    (function() {
        let lastSignal = null;
        setInterval(async () => {
            try {
                const res = await fetch('/__sdf_reload_check');
                const signal = await res.text();
                if (lastSignal && signal !== lastSignal) {
                    window.location.reload();
                }
                lastSignal = signal;
            } catch (e) {}
        }, 1000);
    })();
</script>
JS;
    }
}
