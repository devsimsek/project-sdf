<?php
namespace SDF;

use Closure;

/**
 * Interface Middleware
 * Defines the contract for SDF request middlewares.
 */
interface Middleware
{
    /**
     * Handle the incoming request.
     *
     * @param Request  $request The incoming HTTP request.
     * @param Closure $next    The next middleware in the pipeline.
     * @return mixed Response object or data.
     */
    public function handle(Request $request, Closure $next): mixed;
}

/**
 * Class Pipeline
 * Manages the execution sequence of middlewares.
 */
class Pipeline
{
    /** @var Request $request The incoming request instance. */
    protected Request $request;

    /** @var array $pipes Array of middleware classes. */
    protected array $pipes = [];

    /**
     * Set the object being sent through the pipeline.
     *
     * @param Request $request
     * @return self
     */
    public function send(Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Set the array of pipes.
     *
     * @param array $pipes
     * @return self
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;
        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param Closure $destination Final operation to execute.
     * @return mixed
     */
    public function then(Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->getSlice(),
            $destination
        );

        return $pipeline($this->request);
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return Closure
     */
    protected function getSlice(): Closure
    {
        return function ($stack, $pipe) {
            return function ($request) use ($stack, $pipe) {
                return (new $pipe)->handle($request, $stack);
            };
        };
    }
}
