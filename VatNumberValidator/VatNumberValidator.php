<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Domain\Model\Core\Validator\Constraints;

use SoapClient;
use SoapFault;
use stdClass;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Description of VatNumberValidator
 *
 * @author Daniele Artico <daniele.artico@cogitoweb.it>
 */
class VatNumberValidator extends ConstraintValidator
{
	/**
	 * VAT number length
	 * 
	 * @integer
	 */
	const LENGTH = 11;

	/**
	 * VAT number on-line validation service
	 * 
	 * @const string
	 */
	const WSDL = 'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';

	/**
	 * Country code used to query VAT number on-line validation service
	 * 
	 * @const string
	 */
	const COUNTRY_CODE = 'IT';

	/**
	 * {@inheritdoc}
	 */
	public function validate($value, Constraint $constraint)
	{
		if (!$constraint instanceof VatNumber) {
			throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\VatNumber');
		}

		if (null === $value || '' === $value) {
			return;
		}

		if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
			throw new UnexpectedTypeException($value, 'string');
		}

		$value = (string) $value;

		// Validate format
		if (strlen($value) < self::LENGTH) {
			$this->context
				->buildViolation($constraint->message)
				->setInvalidValue($value)
				->setCode(VatNumber::TOO_SHORT_ERROR)
                ->addViolation()
			;

			return;
		}

		if (strlen($value) > self::LENGTH) {
			$this->context
				->buildViolation($constraint->message)
				->setInvalidValue($value)
				->setCode(VatNumber::TOO_LONG_ERROR)
                ->addViolation()
			;

			return;
		}

		if (!ctype_digit($value)) {
			$this->context
				->buildViolation($constraint->message)
				->setInvalidValue($value)
				->setCode(VatNumber::INVALID_CHARACTERS_ERROR)
                ->addViolation()
			;

			return;
		}

		// Validate value
		$even        = $this->getEvenDigits($value);
		$odd         = $this->getOddDigits($value);
		$doubledEven = $this->doubleDigits($even);
		$lastDigit   = $this->getLastDigit($value);

		$x = array_sum($odd);
		$y = array_sum($doubledEven);
		$z = $this->countDigits($even);
		$t = ($x + $y + $z) % 10;
		$c = (10 - $t) % 10;

		if ($lastDigit !== $c) {
			$this->context
				->buildViolation($constraint->message)
				->setInvalidValue($value)
				->setCode(VatNumber::CHECKSUM_FAILED_ERROR)
                ->addViolation()
			;

			return;
		}

		// Validate if VAT number is valid using on-line validation service
		try {
			$response = $this->consumeApi(self::COUNTRY_CODE, $value);
		} catch (SoapFault $e) {
			// If error, raise warning and quit validation
			trigger_error($e->getMessage(), E_USER_WARNING);

			return;
		}
		
		// Validation service returned and empty response.
		if (!$response) {
			trigger_error('Empty response from VAT number on-line validation service', E_USER_WARNING);

			return;
		}

		// Validation service returned unexpected response
		if (!isset($response->valid)) {
			trigger_error('Unexpected response from VAT number on-line validation service', E_USER_WARNING);

			return;
		}

		if (!$response->valid) {
			$this->context
				->buildViolation($constraint->message)
				->setInvalidValue($value)
				->setCode(VatNumber::ONLINE_VALIDATION_FAILED_ERROR)
                ->addViolation()
			;
		}
	}

	/**
	 * Get even digits
	 * 
	 * @param string $value
	 * 
	 * @return integer[]
	 */
	private function getEvenDigits(string $value)
	{
		preg_match_all('/(\d)(\d)/', $value, $digits);

		return array_map('intval', $digits[2]);
	}

	/**
	 * Get odd digits
	 * 
	 * @param string $value
	 * 
	 * @return integer[]
	 */
	private function getOddDigits(string $value)
	{
		preg_match_all('/(\d)(\d)/', $value, $digits);

		return array_map('intval', $digits[1]);
	}

	/**
	 * Get last digit
	 * 
	 * @param string $value
	 * 
	 * @return integer
	 */
	private function getLastDigit(string $value)
	{
		return (integer) substr($value, -1);
	}

	/**
	 * Double each digit of array
	 * 
	 * @param integer[] $digits
	 * 
	 * @return integer[]
	 */
	private function doubleDigits(array $digits)
	{
		foreach ($digits as &$digit) {
			$digit = $digit * 2;
		}

		return $digits;
	}

	/**
	 * Count number of occurrencies of digits with value >= 5
	 * 
	 * @param integer[] $digits
	 * 
	 * @return integer
	 */
	private function countDigits(array $digits)
	{
		$i = 0;

		foreach ($digits as $digit) {
			if ($digit >= 5) {
				$i ++;
			}
		}

		return $i;
	}

	/**
	 * Consume API
	 * 
	 * @param string $countryCode
	 * @param string $vatNumber
	 * 
	 * @return stdClass
	 */
	private function consumeApi(string $countryCode, string $vatNumber)
	{
		$client = new SoapClient(self::WSDL);

		return $client->checkVat([
			'countryCode' => $countryCode,
			'vatNumber'   => $vatNumber
		]);
	}
}
