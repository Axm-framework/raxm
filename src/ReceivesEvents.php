<?php

namespace Axm\Raxm;

use Axm\Raxm\Event;

/**
 * The ReceivesEvents trait provides functionality for emitting,
 * dispatching, and listening to events in a component.
 */
trait ReceivesEvents
{
    /**
     * The queue of events to be emitted.
     */
    protected array $eventQueue = [];

    /**
     * The queue of browser events to be dispatched.
     */
    protected array $dispatchQueue = [];

    /**
     * The array of event listeners.
     */
    protected array $listeners = [];

    /**
     * Get the array of event listeners.
     */
    protected function getListeners(): array
    {
        return $this->listeners;
    }

    /**
     * Emit a new event to be added to the event queue.
     */
    public function emit(string $event, ...$params): Event
    {
        return $this->eventQueue[] = new Event($event, $params);
    }

    /**
     * Emit an event with the "up" modifier to propagate the event to ancestor components only.
     */
    public function emitUp(string $event, ...$params)
    {
        $this->emit($event, ...$params)->up();
    }

    /**
     * Emit an event with the "self" modifier to handle the event only within the current component.
     */
    public function emitSelf(string $event, ...$params)
    {
        $this->emit($event, ...$params)->self();
    }

    /**
     * Emit an event to a specific component identified by name.
     */
    public function emitTo(string $name, string $event, ...$params)
    {
        $this->emit($event, ...$params)->component(new $name);
    }

    /**
     * Queue a browser event for dispatching.
     */
    public function dispatchBrowserEvent(string $event, mixed $data = null): void
    {
        $this->dispatchQueue[] = [
            'event' => $event,
            'data'  => $data,
        ];
    }

    /**
     * Get the array of events in the event queue.
     */
    public function getEventQueue(): array
    {
        $serializedEvents = array_map(function ($event) {
            return $event->serialize();
        }, $this->eventQueue);

        return array_values($serializedEvents);
    }

    /**
     * Get the array of browser events in the dispatch queue.
     */
    public function getDispatchQueue(): array
    {
        return $this->dispatchQueue;
    }

    /**
     * Get the array of events and their associated handlers.
     */
    protected function getEventsAndHandlers(): array
    {
        $listeners = $this->getListeners();
        $eventsAndHandlers = [];

        foreach ($listeners as $key => $value) {
            $key = is_numeric($key) ? $value : $key;
            $eventsAndHandlers[$key] = $value;
        }

        return $eventsAndHandlers;
    }

    /**
     * Get the array of events being listened for.
     */
    public function getEventsBeingListenedFor(): array
    {
        return array_keys($this->getEventsAndHandlers());
    }

    /**
     * Fire a specified event with parameters and an identifier.
     */
    public function fireEvent(string $event, mixed $params, int $id)
    {
        $method = $this->getEventsAndHandlers()[$event];

        $this->callMethod($method, $params, function ($returned) use ($event, $id) {
            $this->dispatch('action.returned', $this, $event, $returned, $id);
        });
    }

    /**
     * Dispatch a specified event with parameters.
     */
    public function dispatch(string $event, ...$params)
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener(...$params);
        }
    }

    /**
     * Listen for a specified event with a callback.
     */
    public function listen(string $event, $callback): void
    {
        $this->listeners[$event][] = $callback;
    }
}
