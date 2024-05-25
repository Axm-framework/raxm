<?php

namespace Axm\Raxm;

/**
 * The EventBus class manages event listeners and triggers events in the Raxm framework.
 */
class EventBus
{
    /**
     * The array to store regular event listeners.
     */
    protected array $listeners = [];

    /**
     * The array to store event listeners executed before the main event.
     */
    protected array $listenersBefore = [];

    /**
     * The array to store event listeners executed after the main event.
     */
    protected array $listenersAfter = [];

    /**
     * Bootstraps the EventBus as a singleton instance in the application.
     */
    public function boot()
    {
        app()->singleton($this::class);
    }

    /**
     * Registers a callback for a specific event.
     */
    public function on(string $name, callable $callback): callable
    {
        if (!isset($this->listeners[$name]))
            $this->listeners[$name] = [];

        $this->listeners[$name][] = $callback;

        return fn() => $this->off($name, $callback);
    }

    /**
     * Registers a callback to be executed before the main event.
     */
    public function before(string $name, callable $callback): callable
    {
        if (!isset($this->listenersBefore[$name]))
            $this->listenersBefore[$name] = [];

        $this->listenersBefore[$name][] = $callback;

        return fn() => $this->off($name, $callback);
    }

    /**
     * Registers a callback to be executed after the main event.
     */
    public function after(string $name, callable $callback): callable
    {
        if (!isset($this->listenersAfter[$name]))
            $this->listenersAfter[$name] = [];

        $this->listenersAfter[$name][] = $callback;

        return fn() => $this->off($name, $callback);
    }

    /**
     * Unregisters a callback for a specific event.
     */
    public function off(string $name, callable $callback)
    {
        $index = array_search($callback, $this->listeners[$name] ?? []);
        $indexAfter = array_search($callback, $this->listenersAfter[$name] ?? []);
        $indexBefore = array_search($callback, $this->listenersBefore[$name] ?? []);

        if ($index !== false)
            unset($this->listeners[$name][$index]);
        elseif ($indexAfter !== false)
            unset($this->listenersAfter[$name][$indexAfter]);
        elseif ($indexBefore !== false)
            unset($this->listenersBefore[$name][$indexBefore]);
    }

    /**
     * Triggers a specific event, invoking registered callbacks and returning a middleware closure.
     */
    public function trigger(string $name, ...$params): callable
    {
        $middlewares = [];
        $listeners = array_merge(
            ($this->listenersBefore[$name] ?? []),
            ($this->listeners[$name] ?? []),
            ($this->listenersAfter[$name] ?? [])
        );

        foreach ($listeners as $callback) {
            $result = $callback(...$params);

            if ($result) {
                $middlewares[] = $result;
            }
        }

        return function (&$forward = null, ...$extras) use ($middlewares) {
            foreach ($middlewares as $finisher) {
                if ($finisher === null)
                    continue;

                $finisher = is_array($finisher) ? last($finisher) : $finisher;

                $result = $finisher($forward, ...$extras);

                // Only overwrite previous "forward" if something is returned from the callback.
                $forward = $result ?? $forward;
            }

            return $forward;
        };
    }
}
