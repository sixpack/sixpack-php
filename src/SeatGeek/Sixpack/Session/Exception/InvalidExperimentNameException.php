<?php

namespace SeatGeek\Sixpack\Session\Exception;

use \Exception;

/**
 * Used when an experiement name is deemed invalid
 */
class InvalidExperimentNameException extends Exception
{
    /**
     * The sprintf pattern for when an exception is thrown
     *
     * @var string
     */
    protected $messageTemplate = "The experiement name \"%s\" is invalid";

    /**
     * Constructor
     *
     * @param string|null If passed, it's the experiment name
     * @param int $code The exception code
     * @param \Exception $previous The previous exception
     */
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        if ($message) {
            $message = sprintf($this->messageTemplate, $message);
        }
        return parent::__construct($message, $code, $previous);
    }
}
