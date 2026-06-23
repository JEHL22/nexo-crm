function buildPdfPopup() {
    const backdrop = document.createElement('div');
    backdrop.className = [
        'fixed',
        'inset-0',
        'z-[210]',
        'hidden',
        'bg-transparent',
        'pointer-events-auto',
    ].join(' ');
    // Keep the shared PDF viewer above any CRM modal, including Mesa de Control.
    backdrop.style.zIndex = '210';
    backdrop.dataset.pdfPopupBackdrop = 'true';

    const popup = document.createElement('div');
    popup.className = [
        'crm-pdf-popup',
        'fixed',
        'z-[220]',
        'hidden',
        'min-w-[560px]',
        'min-h-[420px]',
        'max-w-[96vw]',
        'max-h-[92vh]',
        'overflow-hidden',
        'rounded-[24px]',
        'border',
        'border-[color:color-mix(in_srgb,var(--crm-secondary)_28%,#cbd5e1)]',
        'bg-white',
        'shadow-2xl',
        'ring-1',
        'ring-black/5',
        'isolate',
        'flex',
        'flex-col',
    ].join(' ');
    popup.style.width = '920px';
    popup.style.height = '760px';
    popup.style.resize = 'none';
    popup.style.zIndex = '220';

    popup.innerHTML = `
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-2 shrink-0 cursor-move select-none" data-pdf-drag-handle>
            <div class="min-w-0">
                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[color:color-mix(in_srgb,var(--crm-primary)_62%,#64748b)]">Visor PDF</div>
                <div class="truncate text-[15px] font-semibold leading-tight text-[color:color-mix(in_srgb,var(--crm-primary)_82%,#0f172a)]" data-pdf-title>Documento</div>
            </div>
            <div class="flex items-center gap-2" data-pdf-controls>
                <button type="button" class="rounded-xl border border-slate-300 bg-white px-3 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-100" data-pdf-open-external>
                    Abrir aparte
                </button>
                <button type="button" class="rounded-full border border-slate-300 bg-white px-2.5 py-0.5 text-sm font-semibold leading-none text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" data-pdf-close>
                    &times;
                </button>
            </div>
        </div>
        <div class="relative min-h-0 flex-1 bg-slate-100 p-3" data-pdf-stage>
            <div class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" data-pdf-error></div>
            <div class="h-full w-full" data-pdf-frame-host></div>
        </div>
        <div
            class="crm-pdf-popup__resize absolute bottom-0 right-0 z-30 h-8 w-8 cursor-se-resize"
            data-pdf-resize-handle
            title="Cambiar tamaño"
            aria-hidden="true"
        ></div>
    `;

    document.body.appendChild(backdrop);
    document.body.appendChild(popup);

    return {
        backdrop,
        popup,
    };
}

function createPdfPopupController() {
    const { popup, backdrop } = buildPdfPopup();
    const title = popup.querySelector('[data-pdf-title]');
    const stage = popup.querySelector('[data-pdf-stage]');
    const frameHost = popup.querySelector('[data-pdf-frame-host]');
    const errorBox = popup.querySelector('[data-pdf-error]');
    const closeButton = popup.querySelector('[data-pdf-close]');
    const externalButton = popup.querySelector('[data-pdf-open-external]');
    const dragHandle = popup.querySelector('[data-pdf-drag-handle]');
    const controlZones = popup.querySelectorAll('[data-pdf-controls]');
    const resizeHandle = popup.querySelector('[data-pdf-resize-handle]');

    let currentUrl = '';
    let resizePointerId = null;
    let startWidth = 0;
    let startHeight = 0;
    let startClientX = 0;
    let startClientY = 0;
    let openSequence = 0;

    function isOpen() {
        return !popup.classList.contains('hidden');
    }

    function containsTarget(target) {
        return target instanceof Node ? popup.contains(target) : false;
    }

    function consumeEvent(event) {
        event.stopPropagation();
    }

    function setError(message = '') {
        errorBox.textContent = message;
        errorBox.classList.toggle('hidden', !message);
        frameHost.classList.toggle('hidden', !!message);
    }

    function clearFrame() {
        frameHost.innerHTML = '';
    }

    function mountFrame(url, sequence) {
        const frame = document.createElement('iframe');
        frame.title = 'Visor PDF';
        frame.className = 'block h-full w-full rounded-2xl border border-slate-200 bg-white';
        frame.setAttribute('loading', 'eager');
        frame.src = `${url}#view=FitH`;

        frame.addEventListener('load', () => {
            if (sequence !== openSequence) {
                return;
            }

            frameHost.classList.remove('hidden');
        });

        clearFrame();
        frameHost.appendChild(frame);
    }

    function syncStageHeight() {
        const topSections = popup.querySelectorAll('.shrink-0');
        const reservedHeight = Array.from(topSections).reduce((sum, item) => sum + item.offsetHeight, 0);
        const nextHeight = Math.max((popup.clientHeight || 760) - reservedHeight, 220);

        stage.style.height = `${nextHeight}px`;
    }

    function open({ url, title: popupTitle }) {
        if (!url) {
            return;
        }

        openSequence += 1;
        currentUrl = url;
        title.textContent = popupTitle || 'Documento PDF';
        backdrop.classList.remove('hidden');
        popup.classList.remove('hidden');
        syncStageHeight();
        setError('');
        frameHost.classList.add('hidden');

        if (!popup.dataset.draggableReady) {
            window.crmPopupUtils?.makeDraggable?.(popup, dragHandle, {
                storageKey: 'crm-pdf-popup-position-v3',
                defaultPosition: 'bottom-right',
            });
            popup.dataset.draggableReady = 'true';
        }

        window.setTimeout(() => {
            if (!isOpen()) {
                return;
            }

            mountFrame(url, openSequence);
        }, 0);
    }

    function close() {
        openSequence += 1;
        backdrop.classList.add('hidden');
        popup.classList.add('hidden');
        currentUrl = '';
        setError('');
        clearFrame();
        frameHost.classList.remove('hidden');
    }

    backdrop.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (isOpen()) {
            close();
        }
    });

    popup.addEventListener('pointerdown', consumeEvent);
    popup.addEventListener('click', consumeEvent);

    const resizeObserver = new ResizeObserver(() => {
        syncStageHeight();
    });

    resizeObserver.observe(popup);

    resizeHandle.addEventListener('pointerdown', (event) => {
        if (event.button > 0) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        resizePointerId = event.pointerId;
        startWidth = popup.offsetWidth;
        startHeight = popup.offsetHeight;
        startClientX = event.clientX;
        startClientY = event.clientY;

        resizeHandle.setPointerCapture(resizePointerId);
        document.body.classList.add('select-none');
    });

    resizeHandle.addEventListener('pointermove', (event) => {
        if (resizePointerId === null || event.pointerId !== resizePointerId) {
            return;
        }

        event.preventDefault();

        const minWidth = 560;
        const minHeight = 420;
        const maxWidth = Math.floor(window.innerWidth * 0.96);
        const maxHeight = Math.floor(window.innerHeight * 0.92);

        const nextWidth = Math.min(
            Math.max(minWidth, startWidth + (event.clientX - startClientX)),
            maxWidth
        );

        const nextHeight = Math.min(
            Math.max(minHeight, startHeight + (event.clientY - startClientY)),
            maxHeight
        );

        popup.style.width = `${nextWidth}px`;
        popup.style.height = `${nextHeight}px`;
        syncStageHeight();
    });

    function stopResize(event) {
        if (resizePointerId === null) {
            return;
        }

        if (event?.pointerId && event.pointerId !== resizePointerId) {
            return;
        }

        try {
            resizeHandle.releasePointerCapture(resizePointerId);
        } catch (error) {
            // ignore
        }

        resizePointerId = null;
        document.body.classList.remove('select-none');
    }

    resizeHandle.addEventListener('pointerup', stopResize);
    resizeHandle.addEventListener('pointercancel', stopResize);

    controlZones.forEach((zone) => {
        zone.addEventListener('pointerdown', (event) => {
            event.stopPropagation();
        });
    });

    closeButton.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        close();
    });

    externalButton.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (currentUrl) {
            const externalWindow = window.open(currentUrl, '_blank');

            if (externalWindow) {
                externalWindow.opener = null;
            } else {
                setError('El navegador bloqueó la nueva pestaña. Permite popups para abrir el PDF aparte.');
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !popup.classList.contains('hidden')) {
            close();
        }
    });

    syncStageHeight();

    return {
        isOpen,
        containsTarget,
        open,
        close,
    };
}

document.addEventListener('DOMContentLoaded', () => {
    const controller = createPdfPopupController();

    window.crmPdfPopup = {
        isOpen() {
            return controller.isOpen();
        },
        containsTarget(target) {
            return controller.containsTarget(target);
        },
        open(config) {
            return controller.open(config);
        },
        close() {
            return controller.close();
        },
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-pdf-popup-url]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        window.crmPdfPopup.open({
            url: trigger.dataset.pdfPopupUrl,
            title: trigger.dataset.pdfPopupTitle || trigger.dataset.pdfPopupName || 'Documento PDF',
        });
    });
});
