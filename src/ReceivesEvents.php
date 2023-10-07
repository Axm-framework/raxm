<?php

namespace Axm\Raxm;

use Axm\Raxm\Event;

trait ReceivesEvents
{
    protected $eventQueue = [];
    protected $dispatchQueue = [];
    protected $listeners = [];

    protected function getListeners()
    {
        return $this->listeners;
    }

    public function emit($event, ...$params)
    {
        return $this->eventQueue[] = new Event($event, $params);
    }

    public function emitUp($event, ...$params)
    {
        $this->emit($event, ...$params)->up();
    }

    public function emitSelf($event, ...$params)
    {
        $this->emit($event, ...$params)->self();
    }

    public function emitTo($name, $event, ...$params)
    {
        $this->emit($event, ...$params)->component($name);
    }

    public function dispatchBrowserEvent($event, $data = null)
    {
        $this->dispatchQueue[] = [
            'event' => $event,
            'data'  => $data,
        ];
    }

    public function getEventQueue()
    {
        $serializedEvents = array_map(function ($event) {
            return $event->serialize();
        }, $this->eventQueue);

        return array_values($serializedEvents);
    }


    public function getDispatchQueue()
    {
        return $this->dispatchQueue;
    }


    protected function getEventsAndHandlers()
    {
        $listeners = $this->getListeners();
        $eventsAndHandlers = [];

        foreach ($listeners as $key => $value) {
            $key = is_numeric($key) ? $value : $key;
            $eventsAndHandlers[$key] = $value;
        }

        return $eventsAndHandlers;
    }

    public function getEventsBeingListenedFor()
    {
        return array_keys($this->getEventsAndHandlers());
    }

    public function fireEvent($event, $params, $id)
    {
        $method = $this->getEventsAndHandlers()[$event];

        $this->callMethod($method, $params, function ($returned) use ($event, $id) {
            $this->dispatch('action.returned', $this, $event, $returned, $id);
        });
    }

    public function dispatch($event, ...$params)
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener(...$params);
        }
    }

    public function listen($event, $callback)
    {
        $this->listeners[$event][] = $callback;
    }
}
