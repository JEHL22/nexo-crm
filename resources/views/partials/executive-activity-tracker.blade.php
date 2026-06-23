@php
    $activityTrackerConfig = [
        'module_name' => $moduleName,
        'route_name' => $routeName,
        'ensure_url' => route('activity.sessions.ensure'),
        'heartbeat_url' => route('activity.sessions.heartbeat'),
        'event_url' => route('activity.sessions.event'),
        'csrf' => csrf_token(),
    ];
@endphp

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const trackerConfig = @json($activityTrackerConfig);

        if (!trackerConfig.module_name) {
            return;
        }

        let started = false;
        let heartbeatTimer = null;
        let isFocused = !document.hidden && document.hasFocus();

        function postJson(url, payload, keepalive = false) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': trackerConfig.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                keepalive,
                body: JSON.stringify(payload),
            });
        }

        async function ensureSession() {
            try {
                await postJson(trackerConfig.ensure_url, {
                    module_name: trackerConfig.module_name,
                    route_name: trackerConfig.route_name,
                    page_url: window.location.href,
                    is_focused: !document.hidden && document.hasFocus(),
                });

                started = true;
                if (!heartbeatTimer) {
                    heartbeatTimer = window.setInterval(sendHeartbeat, 15000);
                }
            } catch (error) {
                console.error(error);
            }
        }

        async function sendHeartbeat() {
            if (!started) return;

            try {
                await postJson(trackerConfig.heartbeat_url, {
                    module_name: trackerConfig.module_name,
                    route_name: trackerConfig.route_name,
                    page_url: window.location.href,
                    is_focused: !document.hidden && document.hasFocus(),
                });
            } catch (error) {
                console.error(error);
            }
        }

        async function sendEvent(eventType, label, meta = null) {
            if (!started) return;

            try {
                await postJson(trackerConfig.event_url, {
                    event_type: eventType,
                    label,
                    module_name: trackerConfig.module_name,
                    route_name: trackerConfig.route_name,
                    page_url: window.location.href,
                    meta,
                }, eventType === 'focus_lost');
            } catch (error) {
                console.error(error);
            }
        }

        document.addEventListener('visibilitychange', () => {
            const nowFocused = !document.hidden;

            if (nowFocused === isFocused) return;

            isFocused = nowFocused;
            sendEvent(nowFocused ? 'focus_regained' : 'focus_lost', nowFocused ? 'Regresó al CRM' : 'Salió del CRM');
        });

        window.addEventListener('focus', () => {
            if (!isFocused && !document.hidden) {
                isFocused = true;
                sendEvent('focus_regained', 'Regresó al CRM');
            }
        });

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;

            const label = event.submitter?.textContent?.trim()
                || form.getAttribute('data-activity-label')
                || 'Formulario enviado';

            sendEvent('page_action', label, {
                action: form.getAttribute('action') || null,
                method: form.getAttribute('method') || null,
            });
        }, true);

        ensureSession();
    });
</script>
