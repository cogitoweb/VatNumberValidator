<?php

namespace Tests\Domain\Model\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraints\VatNumber;
use Symfony\Component\Validator\Constraints\VatNumberValidator;
use stdClass;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class VatNumberTest extends ConstraintValidatorTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createValidator()
    {
        return new VatNumberValidator;
    }

    public function testNullIsValid()
    {
        $this->validator->validate(null, new VatNumber());
        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid()
    {
        $this->validator->validate('', new VatNumber());
        $this->assertNoViolation();
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testExpectsStringCompatibleType()
    {
        $this->validator->validate(new stdClass(), new VatNumber());
    }

    public function testValidVatNumber()
    {
        $this->validator->validate('01743030304', new VatNumber());
        $this->assertNoViolation();
    }

    /**
     *
     * @dataProvider getInvalidVatNumbers
     */
    public function testInvalidVatNumber($vatNumber, $code)
    {
        $constraint = new VatNumber('My message');

        $this->validator->validate($vatNumber, $constraint);
        $this->buildViolation('My message')
            ->setInvalidValue($vatNumber)
            ->setCode($code)
            ->assertRaised()
        ;
    }

    /**
     * List of invalid VAT numbers
     * with corresponding error codes.
     *
     * @return array[]
     */
    public function getInvalidVatNumbers()
    {
        return [
            ['1234567890',   VatNumber::TOO_SHORT_ERROR],
            ['123456789012', VatNumber::TOO_LONG_ERROR],
            ['abcde123456',  VatNumber::INVALID_CHARACTERS_ERROR],
            ['12345678901',  VatNumber::CHECKSUM_FAILED_ERROR],
            ['12345678903',  VatNumber::ONLINE_VALIDATION_FAILED_ERROR]
        ];
    }
}
