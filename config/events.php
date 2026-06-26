<?php
// config/events.php — Dispatcher de eventos sincrónico (in-process)
//
// Uso:
//   listen('proyecto.creado', function($payload) { ... });
//   dispatch('proyecto.creado', ['proyecto_id' => 42]);

$_event_listeners = [];

/**
 * Registra un listener para un evento.
 */
function listen(string $event, callable $listener): void {
    global $_event_listeners;
    $_event_listeners[$event][] = $listener;
}

/**
 * Despacha un evento ejecutando todos los listeners registrados.
 */
function dispatch(string $event, array $payload = []): void {
    global $_event_listeners;
    foreach ($_event_listeners[$event] ?? [] as $listener) {
        $listener($payload);
    }
}
