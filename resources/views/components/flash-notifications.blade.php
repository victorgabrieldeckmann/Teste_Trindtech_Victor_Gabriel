@php
    $notifications = collect([
        ['type' => 'success', 'title' => 'Sucesso', 'message' => session('success')],
        ['type' => 'danger', 'title' => 'Atenção', 'message' => session('error')],
    ])->filter(fn (array $notification) => filled($notification['message']))->values();
@endphp

<div
    class="toast-container position-fixed top-0 end-0 p-3"
    style="z-index: 1090"
    data-flash-toast-container
></div>

<template id="flash-toast-template">
    <div class="toast border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <strong class="d-block mb-1" data-flash-toast-title></strong>
                <span data-flash-toast-message></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
        </div>
    </div>
</template>

@push('scripts')
    <script>
        (() => {
            const storageKey = 'painel-indicadores.notifications';
            const lifetime = 3000;
            const now = Date.now();
            const container = document.querySelector('[data-flash-toast-container]');
            const template = document.getElementById('flash-toast-template');
            const currentNotifications = {{ Illuminate\Support\Js::from($notifications) }};

            const readStoredNotifications = () => {
                try {
                    return JSON.parse(sessionStorage.getItem(storageKey) ?? '[]');
                } catch {
                    return [];
                }
            };

            const storeNotifications = notifications => {
                try {
                    sessionStorage.setItem(storageKey, JSON.stringify(notifications));
                } catch {
                    // A notificação continua funcionando mesmo sem armazenamento disponível.
                }
            };

            const createId = () => window.crypto?.randomUUID?.()
                ?? `${Date.now()}-${Math.random()}`;

            const storedNotifications = readStoredNotifications()
                .filter(notification => now - notification.createdAt < lifetime);
            const newNotifications = currentNotifications.map(notification => ({
                ...notification,
                id: createId(),
                createdAt: now,
            }));
            let activeNotifications = [...storedNotifications, ...newNotifications].slice(-5);

            storeNotifications(activeNotifications);

            activeNotifications.forEach(notification => {
                const element = template.content.firstElementChild.cloneNode(true);
                const remainingTime = Math.max(1, lifetime - (now - notification.createdAt));

                element.dataset.notificationId = notification.id;
                element.classList.add(`text-bg-${notification.type}`);
                element.querySelector('[data-flash-toast-title]').textContent = notification.title;
                element.querySelector('[data-flash-toast-message]').textContent = notification.message;
                element.addEventListener('hidden.bs.toast', () => {
                    activeNotifications = activeNotifications.filter(item => item.id !== notification.id);
                    storeNotifications(activeNotifications);
                    element.remove();
                });

                container.append(element);
                bootstrap.Toast.getOrCreateInstance(element, {
                    autohide: true,
                    delay: remainingTime,
                }).show();
            });
        })();
    </script>
@endpush
