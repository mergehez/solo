<?php

namespace AaronFrancis\Solo\Hotkeys;

use AaronFrancis\Solo\Commands\Command;
use AaronFrancis\Solo\Prompt\Dashboard;
use Chewie\Input\KeyPressListener;
use Closure;
use Laravel\Prompts\Key;
use Laravel\SerializableClosure\Support\ReflectionClosure;
use ReflectionParameter;

class Hotkey
{
    protected Dashboard $prompt;

    protected Command $command;

    protected ?Closure $displayUsing = null;

    protected Closure $handler;

    protected ?KeyHandler $fromKeyHandler = null;

    public static function make(mixed ...$arguments): static
    {
        return new static(...$arguments);
    }

    public function __construct(public array|string $keys, KeyHandler|Closure $handler)
    {
        if ($handler instanceof KeyHandler) {
            $this->fromKeyHandler = $handler;
            $handler = $handler->handler();
        }

        $this->handler = $handler;
    }

    public function init(Command $command, Dashboard $prompt): void
    {
        $this->command = $command;
        $this->prompt = $prompt;
    }

    public function handle(): mixed
    {
        return $this->callWithParams($this->handler);
    }

    public function remap(array|string $keys): static
    {
        $this->keys = $keys;

        return $this;
    }

    public function visible()
    {

    }

    public function active()
    {

    }

    public function display(?Closure $cb): static
    {
        $this->displayUsing = $cb;

        return $this;
    }

    public function hidden()
    {
        return $this->display(fn() => false);
    }

    public function callWithParams(Closure $closure): mixed
    {
        $reflected = new ReflectionClosure($closure);

        $arguments = collect($reflected->getParameters())->map(function (ReflectionParameter $parameter) {
            return match ($parameter->getType()->getName()) {
                Command::class => $this->command,
                Dashboard::class => $this->prompt,
                KeyPressListener::class => $this->prompt->listener,
                Hotkey::class => $this,
                default => null
            };
        });

        return call_user_func($closure, ...$arguments->all());
    }

}