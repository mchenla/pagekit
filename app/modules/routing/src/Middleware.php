<?php

namespace Pagekit\Routing;

use Pagekit\Event\EventDispatcherInterface;

class Middleware
{
    /**
     * @var EventDispatcherInterface
     */
    protected $events;

    /**
     * Constructor.
     *
     * @param $events
     */
    public function __construct(EventDispatcherInterface $events)
    {
        $this->events = $events;

        $events->on('app.request', function ($event, $request) {
            if ($name = $request->attributes->get('_route', '')) {
                $event->getDispatcher()->trigger('before'.$name, [$request]);
            }
        }, 50);

        $events->on('app.response', function ($event, $request) {
            if ($name = $request->attributes->get('_route', '')) {
                $event->getDispatcher()->trigger('after'.$name, [$request]);
            }
        }, 50);
    }

    /**
     * Sets a callback to act before
     *
     * @param string   $name
     * @param callable $callback
     * @param int      $priority
     */
    public function before($name, $callback, $priority)
    {
        $this->events->on('before'.$name, $callback, $priority);
    }

    /**
     * @param string   $name
     * @param callable $callback
     * @param int      $priority
     */
    public function after($name, $callback, $priority)
    {
        $this->events->on('after'.$name, $callback, $priority);
    }
}
