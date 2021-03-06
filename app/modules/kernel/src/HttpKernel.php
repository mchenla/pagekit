<?php

namespace Pagekit\Kernel;

use Pagekit\Event\EventDispatcherInterface;
use Pagekit\Exception\HttpException;
use Pagekit\Kernel\Event\ControllerEvent;
use Pagekit\Kernel\Event\ExceptionEvent;
use Pagekit\Kernel\Event\KernelEvent;
use Pagekit\Kernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class HttpKernel implements HttpKernelInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $events;

    /**
     * @var RequestStack
     */
    protected $stack;

    /**
     * Constructor.
     *
     * @param EventDispatcherInterface $events
     * @param RequestStack             $stack
     */
    public function __construct(EventDispatcherInterface $events, RequestStack $stack = null)
    {
        $this->events = $events;
        $this->stack  = $stack ?: new RequestStack();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        return $this->stack->getCurrentRequest();
    }

    /**
     * {@inheritdoc}
     */
    public function isMasterRequest()
    {
        return $this->getRequest() === $this->stack->getMasterRequest();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request)
    {
        try {

            $event = new RequestEvent('app.request', $this);

            $this->stack->push($request);
            $this->events->trigger($event, [$request]);

            if ($event->hasResponse()) {
                $response = $event->getResponse();
            } else {
                $response = $this->handleController();
            }

            return $this->handleResponse($response);

        } catch (\Exception $e) {

            return $this->handleException($e);
        }
    }

    /**
     * Handles the controller event.
     *
     * @return Response
     */
    protected function handleController()
    {
        $event = new ControllerEvent('app.controller', $this);
        $this->events->trigger($event, [$this->getRequest()]);

        $response = $event->getResponse();

        if (!$response instanceof Response) {

            $msg = 'The controller must return a response.';

            if ($response === null) {
                $msg .= ' Did you forget to add a return statement somewhere in your controller?';
            }

            throw new \LogicException($msg);
        }

        return $response;
    }

    /**
     * Handles the response event.
     *
     * @param  Response $response
     * @return Response
     */
    protected function handleResponse(Response $response)
    {
        $event = new KernelEvent('app.response', $this);
        $this->events->trigger($event, [$this->getRequest(), $response]);
        $this->stack->pop();

        return $response;
    }

    /**
     * Handles the exception event.
     *
     * @param  \Exception $e
     * @return Response
     */
    protected function handleException(\Exception $e)
    {
        $event = new ExceptionEvent('app.exception', $this, $e);
        $this->events->trigger($event, [$this->getRequest()]);

        $e = $event->getException();

        if (!$event->hasResponse()) {
            throw $e;
        }

        $response = $event->getResponse();

        if (!$response->isClientError() && !$response->isServerError() && !$response->isRedirect()) {
            if ($e instanceof HttpException) {
                $response->setStatusCode($e->getCode());
            } else {
                $response->setStatusCode(500);
            }
        }

        try {

            return $this->handleResponse($response);

        } catch (\Exception $e) {

            return $response;
        }
    }
}
