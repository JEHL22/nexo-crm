@php
    $trackerConfig = [
        'lead_id' => $leadId,
        'module_name' => $moduleName,
        'route_name' => $routeName,
        'start_url' => route('tmo.sessions.start'),
        'heartbeat_url' => route('tmo.sessions.heartbeat'),
        'stop_url' => route('tmo.sessions.stop'),
        'csrf' => csrf_token(),
    ];
@endphp

<div
    id="executiveTmoBadge"
    class="crm-tmo-badge fixed bottom-4 right-4 z-40 hidden cursor-move select-none touch-none rounded-2xl border px-4 py-3 shadow-xl backdrop-blur"
    style="touch-action: none;"
>
    <div class="crm-tmo-badge__eyebrow text-[11px] font-semibold uppercase tracking-[0.2em]">TMO en vivo</div>
    <div id="executiveTmoModule" class="crm-tmo-badge__module mt-1 text-sm font-semibold"></div>
    <div id="executiveTmoTimer" class="crm-tmo-badge__timer mt-1 text-2xl font-black">00:00</div>
    <div id="executiveTmoHint" class="crm-tmo-badge__eyebrow mt-1 text-xs">Gestionando este lead</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const trackerConfig = @json($trackerConfig);
        const badge = document.getElementById('executiveTmoBadge');
        const moduleLabel = document.getElementById('executiveTmoModule');
        const timerLabel = document.getElementById('executiveTmoTimer');
        const hintLabel = document.getElementById('executiveTmoHint');
        const storageKey = 'executive-tmo-badge-position';

        if (!trackerConfig.lead_id || !trackerConfig.module_name) {
            return;
        }

        let sessionId = null;
        let heartbeatTimer = null;
        let clockTimer = null;
        let startInFlight = false;
        let startedAt = null;
        let isDragging = false;
        let dragPointerId = null;
        let dragOffsetX = 0;
        let dragOffsetY = 0;

        const moduleMap = {
            a_negociar: 'Apartado: A negociar',
            mi_chamba: 'Apartado: Mi chamba',
        };

        function getViewportLimit(value, max) {
            return Math.min(Math.max(value, 12), Math.max(12, max - 12));
        }

        function saveBadgePosition(left, top) {
            try {
                window.localStorage.setItem(storageKey, JSON.stringify({ left, top }));
            } catch (error) {
                console.error(error);
            }
        }

        function applyBadgePosition(left, top, persist = true) {
            if (!badge) {
                return;
            }

            const badgeWidth = badge.offsetWidth;
            const badgeHeight = badge.offsetHeight;
            const safeLeft = getViewportLimit(left, window.innerWidth - badgeWidth);
            const safeTop = getViewportLimit(top, window.innerHeight - badgeHeight);

            badge.style.left = `${safeLeft}px`;
            badge.style.top = `${safeTop}px`;
            badge.style.right = 'auto';
            badge.style.bottom = 'auto';

            if (persist) {
                saveBadgePosition(safeLeft, safeTop);
            }
        }

        function loadStoredBadgePosition() {
            if (!badge) {
                return;
            }

            try {
                const rawValue = window.localStorage.getItem(storageKey);

                if (!rawValue) {
                    return;
                }

                const parsedValue = JSON.parse(rawValue);

                if (typeof parsedValue.left !== 'number' || typeof parsedValue.top !== 'number') {
                    return;
                }

                applyBadgePosition(parsedValue.left, parsedValue.top, false);
            } catch (error) {
                console.error(error);
            }
        }

        function setDefaultBadgePosition() {
            if (!badge) {
                return;
            }

            const badgeWidth = badge.offsetWidth;
            const badgeHeight = badge.offsetHeight;
            const defaultLeft = window.innerWidth - badgeWidth - 16;
            const defaultTop = window.innerHeight - badgeHeight - 16;

            applyBadgePosition(defaultLeft, defaultTop);
        }

        function ensureBadgePosition() {
            if (!badge) {
                return;
            }

            const hasCustomPosition = badge.dataset.positionInitialized === 'true';

            if (!hasCustomPosition) {
                loadStoredBadgePosition();

                if (!badge.style.left || !badge.style.top) {
                    setDefaultBadgePosition();
                }

                badge.dataset.positionInitialized = 'true';
                return;
            }

            const currentLeft = parseFloat(badge.style.left || '0');
            const currentTop = parseFloat(badge.style.top || '0');
            applyBadgePosition(currentLeft, currentTop, false);
        }

        function startDrag(event) {
            if (!badge || event.button > 0) {
                return;
            }

            const rect = badge.getBoundingClientRect();
            isDragging = true;
            dragPointerId = event.pointerId;
            dragOffsetX = event.clientX - rect.left;
            dragOffsetY = event.clientY - rect.top;

            badge.setPointerCapture(event.pointerId);
        }

        function moveDrag(event) {
            if (!isDragging || dragPointerId !== event.pointerId) {
                return;
            }

            applyBadgePosition(event.clientX - dragOffsetX, event.clientY - dragOffsetY);
        }

        function endDrag(event) {
            if (!badge || dragPointerId !== event.pointerId) {
                return;
            }

            isDragging = false;
            badge.releasePointerCapture(event.pointerId);
            dragPointerId = null;
        }

        function formatDuration(totalSeconds) {
            const safeSeconds = Math.max(0, totalSeconds);
            const hours = Math.floor(safeSeconds / 3600);
            const minutes = Math.floor((safeSeconds % 3600) / 60);
            const seconds = safeSeconds % 60;

            if (hours > 0) {
                return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }

            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        function renderClock() {
            if (!badge || !moduleLabel || !timerLabel || !startedAt) {
                return;
            }

            ensureBadgePosition();

            const elapsedSeconds = Math.floor((Date.now() - startedAt.getTime()) / 1000);
            const isOverThreshold = elapsedSeconds > 60;

            badge.classList.remove('hidden');
            badge.classList.toggle('crm-tmo-badge--alert', isOverThreshold);

            moduleLabel.textContent = moduleMap[trackerConfig.module_name] ?? 'Apartado actual';
            timerLabel.textContent = formatDuration(elapsedSeconds);

            if (hintLabel) {
                hintLabel.textContent = isOverThreshold
                    ? 'Superaste 1 minuto en este lead'
                    : 'Gestionando este lead';
            }
        }

        function startClock(isoValue) {
            startedAt = isoValue ? new Date(isoValue) : new Date();
            renderClock();

            if (!clockTimer) {
                clockTimer = window.setInterval(renderClock, 1000);
            }
        }

        function stopClock() {
            if (clockTimer) {
                window.clearInterval(clockTimer);
                clockTimer = null;
            }

            startedAt = null;

            if (badge) {
                badge.classList.add('hidden');
            }
        }

        function ensureHeartbeatTimer() {
            if (sessionId && !heartbeatTimer) {
                heartbeatTimer = window.setInterval(sendHeartbeat, 10000);
            }
        }

        function pauseHeartbeatTimer() {
            if (heartbeatTimer) {
                window.clearInterval(heartbeatTimer);
                heartbeatTimer = null;
            }
        }

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

        async function startTracking() {
            if (startInFlight || document.hidden || sessionId) {
                return;
            }

            startInFlight = true;

            try {
                const response = await postJson(trackerConfig.start_url, {
                    lead_id: trackerConfig.lead_id,
                    module_name: trackerConfig.module_name,
                    route_name: trackerConfig.route_name,
                });

                if (!response.ok) {
                    throw new Error('No se pudo iniciar la sesión TMO.');
                }

                const payload = await response.json();
                sessionId = payload.session_id ?? null;

                ensureHeartbeatTimer();

                startClock(payload.started_at ?? null);
            } catch (error) {
                console.error(error);
            } finally {
                startInFlight = false;
            }
        }

        async function sendHeartbeat() {
            if (!sessionId || document.hidden) {
                return;
            }

            try {
                await postJson(trackerConfig.heartbeat_url, {
                    session_id: sessionId,
                });
            } catch (error) {
                console.error(error);
            }
        }

        async function stopTracking() {
            if (!sessionId) {
                return;
            }

            const currentSessionId = sessionId;
            sessionId = null;

            pauseHeartbeatTimer();

            stopClock();

            try {
                await postJson(trackerConfig.stop_url, {
                    session_id: currentSessionId,
                }, true);
            } catch (error) {
                console.error(error);
            }
        }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                pauseHeartbeatTimer();
                return;
            }

            if (sessionId) {
                ensureHeartbeatTimer();
                sendHeartbeat();
                return;
            }

            startTracking();
        });

        window.addEventListener('pagehide', stopTracking);
        window.addEventListener('beforeunload', stopTracking);
        window.addEventListener('resize', ensureBadgePosition);

        badge?.addEventListener('pointerdown', startDrag);
        badge?.addEventListener('pointermove', moveDrag);
        badge?.addEventListener('pointerup', endDrag);
        badge?.addEventListener('pointercancel', endDrag);

        startTracking();
    });
</script>
