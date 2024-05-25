<?php

namespace Axm\Raxm;

/**
 * The Event class represents an event in the Raxm framework.
 */
class Event
{
    /**
     * The name of the event.
     */
    protected string $name;

    /**
     * The parameters associated with the event.
     */
    protected array $params;

    /**
     * Flag indicating if the event should propagate up to ancestors only.
     */
    protected bool $up;

    /**
     * Flag indicating if the event should be limited to the current component.
     */
    protected bool $self;

    /**
     * The target component for the event.
     */
    protected object $component;

    /**
     * Create a new Event instance.
     */
    public function __construct(string $name, array $params)
    {
        $this->name = $name;
        $this->params = $params;
    }

    /**
     * Set the event to propagate up to ancestors only.
     */
    public function up(): self
    {
        $this->up = true;
        return $this;
    }

    /**
     * Set the event to be limited to the current component.
     */
    public function self(): self
    {
        $this->self = true;
        return $this;
    }

    /**
     * Set the target component for the event.
     */
    public function component(Object $class): self
    {
        $this->component = $class;
        return $this;
    }

    /**
     * Specify the target for the event (no actual functionality).
     */
    public function to(): self
    {
        return $this;
    }

    /**
     * Serialize the event to an array.
     */
    public function serialize(): array
    {
        $output = [
            'event'  => $this->name,
            'params' => $this->params,
        ];

        if ($this->up) $output['ancestorsOnly'] = true;
        if ($this->self) $output['selfOnly'] = true;
        if ($this->component) $output['to'] = is_subclass_of($this->component, Component::class)
            ? $this->component->getComponentName()
            : $this->component;

        return $output;
    }
}
