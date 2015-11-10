<?php

namespace SeatGeek\Sixpack\Session\Exception;

use \Exception;

/**
 * Used when a forced alternative is requested that doesn't exist
 */
class InvalidForcedAlternativeException extends Exception
{
    /**
     * The sprintf pattern for when an exception is thrown
     *
     * @var string
     */
    protected $messageTemplate = "The alternative \"%s\" is not one of the possibilities (%s)";

    /**
     * Constructor
     *
     * @param string|array Either the literal message, or arguments for the message template
     * @param int $code The exception code
     * @param \Exception $previous The previous exception
     */
    public function __construct($message = [], $code = 0, Exception $previous = null)
    {
        if ($message && is_array($message)) {
            $message += ['the alternative', ['the', 'possibilities']];
            $message[1] = implode(', ', $message[1]);
            $message = vsprintf($this->messageTemplate, $message);
        }
        return parent::__construct($message, $code, $previous);
    }
}
