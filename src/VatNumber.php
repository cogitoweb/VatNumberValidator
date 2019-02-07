<?php

declare(strict_types = 1);

namespace Symfony\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class VatNumber extends Constraint
{
    /**
     * Error codes
     *
     * @const string
     */
    const TOO_SHORT_ERROR                = 'cd0fe857-e33f-4392-9792-1f0ada9082fd';
    const TOO_LONG_ERROR                 = '97a9b32b-29f4-4307-ab1f-58299f024d03';
    const INVALID_CHARACTERS_ERROR       = 'cf239da4-a7ce-47b4-8f9c-2e16ae8f65d3';
    const CHECKSUM_FAILED_ERROR          = '6da32eb5-d6da-4d83-993c-e2dfed8c67a3';
    const ONLINE_VALIDATION_FAILED_ERROR = '27f5b3a0-ad06-4f5e-8ccf-2cd2c5841cd9';

    /**
     * Error names
     *
     * @var string[]
     */
    protected static $errorNames = [
        self::TOO_SHORT_ERROR                => 'TOO_SHORT_ERROR',
        self::TOO_LONG_ERROR                 => 'TOO_LONG_ERROR',
        self::INVALID_CHARACTERS_ERROR       => 'INVALID_CHARACTERS_ERROR',
        self::CHECKSUM_FAILED_ERROR          => 'CHECKSUM_FAILED_ERROR',
        self::ONLINE_VALIDATION_FAILED_ERROR => 'ONLINE_VALIDATION_FAILED_ERROR'
    ];

    /**
     * Error message
     *
     * @var string
     */
    public $message = 'Invalid VAT number';

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'message';
    }
}
