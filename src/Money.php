<?php

namespace Money;

use InvalidArgumentException;
use RuntimeException;
use JsonSerializable;

/**
 * Money Value Object.
 *
 * @author Mathias Verraes
 */
final class Money implements JsonSerializable
{
    const ROUND_HALF_UP = PHP_ROUND_HALF_UP;
    const ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;
    const ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN;
    const ROUND_HALF_ODD = PHP_ROUND_HALF_ODD;
    const ROUND_UP = 5;
    const ROUND_DOWN = 6;

    /**
     * Internal value.
     *
     * @var int
     */
    private $amount;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var Calculator
     */
    private static $calculator;

    /**
     * @var array
     */
    private static $calculators = [
        'Money\\Calculator\\BcMathCalculator',
        'Money\\Calculator\\GmpCalculator',
        'Money\\Calculator\\PhpCalculator',
    ];

    /**
     * @param int|string $amount   Amount, expressed in the smallest units of $currency (eg cents)
     * @param Currency $currency
     *
     * @throws InvalidArgumentException If amount is not integer
     */
    public function __construct($amount, Currency $currency)
    {
        if (filter_var($amount, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('Amount must be an integer');
        }

        $this->amount = (string) $amount;
        $this->currency = $currency;
    }

    /**
     * Convenience factory method for a Money object.
     *
     * <code>
     * $fiveDollar = Money::USD(500);
     * </code>
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return Money
     *
     * @throws InvalidArgumentException If amount is not integer
     */
    public static function __callStatic($method, $arguments)
    {
        return new self($arguments[0], new Currency($method));
    }

    /**
     * Returns a new Money instance based on the current one using the Currency.
     *
     * @param int|string $amount
     *
     * @return Money
     *
     * @throws InvalidArgumentException If amount is not integer
     */
    private function newInstance($amount)
    {
        return new self($amount, $this->currency);
    }

    /**
     * Checks whether a Money has the same Currency as this.
     *
     * @param Money $other
     *
     * @return bool
     */
    public function isSameCurrency(Money $other)
    {
        return $this->currency->equals($other->currency);
    }

    /**
     * Asserts that a Money has the same currency as this.
     *
     * @param Money $other
     *
     * @throws InvalidArgumentException If $other has a different currency
     */
    private function assertSameCurrency(Money $other)
    {
        if (!$this->isSameCurrency($other)) {
            throw new InvalidArgumentException('Currencies must be identical');
        }
    }

    /**
     * Checks whether the value represented by this object equals to the other.
     *
     * @param Money $other
     *
     * @return bool
     */
    public function equals(Money $other)
    {
        return $this->isSameCurrency($other) && $this->amount === $other->amount;
    }

    /**
     * Returns an integer less than, equal to, or greater than zero
     * if the value of this object is considered to be respectively
     * less than, equal to, or greater than the other.
     *
     * @param Money $other
     *
     * @return int
     */
    public function compare(Money $other)
    {
        $this->assertSameCurrency($other);

        return $this->getCalculator()->compare($this->amount, $other->amount);
    }

    /**
     * Checks whether the value represented by this object is greater than the other.
     *
     * @param Money $other
     *
     * @return bool
     */
    public function greaterThan(Money $other)
    {
        return 1 === $this->compare($other);
    }

    /**
     * @param \Money\Money $other
     *
     * @return bool
     */
    public function greaterThanOrEqual(Money $other)
    {
        return 0 <= $this->compare($other);
    }

    /**
     * Checks whether the value represented by this object is less than the other.
     *
     * @param Money $other
     *
     * @return bool
     */
    public function lessThan(Money $other)
    {
        return -1 === $this->compare($other);
    }

    /**
     * @param \Money\Money $other
     *
     * @return bool
     */
    public function lessThanOrEqual(Money $other)
    {
        return 0 >= $this->compare($other);
    }

    /**
     * Returns the value represented by this object.
     *
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Returns the currency of this object.
     *
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Returns a new Money object that represents
     * the sum of this and an other Money object.
     *
     * @param Money $addend
     *
     * @return Money
     */
    public function add(Money $addend)
    {
        $this->assertSameCurrency($addend);

        return new self($this->getCalculator()->add($this->amount, $addend->amount), $this->currency);
    }

    /**
     * Returns a new Money object that represents
     * the difference of this and an other Money object.
     *
     * @param Money $subtrahend
     *
     * @return Money
     */
    public function subtract(Money $subtrahend)
    {
        $this->assertSameCurrency($subtrahend);

        return new self($this->getCalculator()->subtract($this->amount, $subtrahend->amount), $this->currency);
    }

    /**
     * Asserts that the operand is integer or float.
     *
     * @throws InvalidArgumentException If $operand is neither integer nor float
     */
    private function assertOperand($operand)
    {
        if (!is_numeric($operand)) {
            throw new InvalidArgumentException('Operand should be a numeric value');
        }
    }

    /**
     * Asserts that rounding mode is a valid integer value.
     *
     * @param int $roundingMode
     *
     * @throws InvalidArgumentException If $roundingMode is not valid
     */
    private function assertRoundingMode($roundingMode)
    {
        if (!in_array(
            $roundingMode, [
                self::ROUND_HALF_DOWN, self::ROUND_HALF_EVEN, self::ROUND_HALF_ODD,
                self::ROUND_HALF_UP, self::ROUND_UP, self::ROUND_DOWN,
            ], true
        )) {
            throw new InvalidArgumentException(
                'Rounding mode should be Money::ROUND_HALF_DOWN | '.
                'Money::ROUND_HALF_EVEN | Money::ROUND_HALF_ODD | '.
                'Money::ROUND_HALF_UP | Money::ROUND_UP | Money::ROUND_DOWN'
            );
        }
    }

    /**
     * Returns a new Money object that represents
     * the multiplied value by the given factor.
     *
     * @param float|int|string $multiplier
     * @param int     $roundingMode
     *
     * @return Money
     */
    public function multiply($multiplier, $roundingMode = self::ROUND_HALF_UP)
    {
        $this->assertOperand($multiplier);
        $this->assertRoundingMode($roundingMode);

        $product = $this->round($this->getCalculator()->multiply($this->amount, $multiplier), $roundingMode);

        return $this->newInstance($product);
    }

    /**
     * @param Currency  $targetCurrency
     * @param float|int $conversionRate
     * @param int       $roundingMode
     *
     * @return Money
     */
    public function convert(Currency $targetCurrency, $conversionRate, $roundingMode = self::ROUND_HALF_UP)
    {
        $this->assertRoundingMode($roundingMode);
        $amount = $this->getCalculator()->round($this->getCalculator()->multiply($this->amount, $conversionRate), $roundingMode);

        return new Money($amount, $targetCurrency);
    }

    /**
     * Returns a new Money object that represents
     * the divided value by the given factor.
     *
     * @param float|int|string $divisor
     * @param int     $roundingMode
     *
     * @return Money
     */
    public function divide($divisor, $roundingMode = self::ROUND_HALF_UP)
    {
        $this->assertOperand($divisor);
        $this->assertRoundingMode($roundingMode);

        if ($divisor === 0 || $divisor === 0.0) {
            throw new InvalidArgumentException('Division by zero');
        }

        $quotient = $this->round($this->getCalculator()->divide($this->amount, $divisor), $roundingMode);

        return $this->newInstance($quotient);
    }

    /**
     * Allocate the money according to a list of ratios.
     *
     * @param array $ratios
     *
     * @return Money[]
     */
    public function allocate(array $ratios)
    {
        $remainder = $this->amount;
        $results = [];
        $total = array_sum($ratios);

        foreach ($ratios as $ratio) {
            $share = $this->getCalculator()->share($this->amount, $ratio, $total);
            $results[] = $this->newInstance($share);
            $remainder = $this->getCalculator()->subtract($remainder, $share);
        }

        for ($i = 0; $this->getCalculator()->compare($remainder, 0) === 1; $i++) {
            $results[$i]->amount = $this->getCalculator()->add($results[$i]->amount, 1);
            $remainder = $this->getCalculator()->subtract($remainder, 1);
        }

        return $results;
    }

    /**
     * Allocate the money among N targets.
     *
     * @param int $n
     *
     * @return Money[]
     *
     * @throws InvalidArgumentException If number of targets is not an integer
     */
    public function allocateTo($n)
    {
        if (!is_int($n)) {
            throw new InvalidArgumentException('Number of targets must be an integer');
        }

        return $this->allocate(array_fill(0, $n, 1));
    }

    /**
     * @param int|float $amount
     * @param $rounding_mode
     *
     * @return string
     */
    private function round($amount, $rounding_mode)
    {
        $this->assertRoundingMode($rounding_mode);
        if ($rounding_mode === self::ROUND_UP) {
            return $this->getCalculator()->ceil($amount);
        }
        if ($rounding_mode === self::ROUND_DOWN) {
            return $this->getCalculator()->floor($amount);
        }

        return $this->getCalculator()->round($amount, $rounding_mode);
    }

    /**
     * Checks if the value represented by this object is zero.
     *
     * @return bool
     */
    public function isZero()
    {
        return 0 === $this->getCalculator()->compare($this->amount, 0);
    }

    /**
     * Checks if the value represented by this object is positive.
     *
     * @return bool
     */
    public function isPositive()
    {
        return 1 === $this->getCalculator()->compare($this->amount, 0);
    }

    /**
     * Checks if the value represented by this object is negative.
     *
     * @return bool
     */
    public function isNegative()
    {
        return -1 === $this->getCalculator()->compare($this->amount, 0);
    }

    /**
     * Creates units from string.
     *
     * @param string $string
     *
     * @return string
     *
     * @throws InvalidArgumentException If $string cannot be parsed
     */
    public static function stringToUnits($string)
    {
        $sign = "(?P<sign>[-\+])?";
        $digits = "(?P<digits>\d*)";
        $separator = '(?P<separator>[.,])?';
        $decimals = "(?P<decimal1>\d)?(?P<decimal2>\d)?";
        $pattern = '/^'.$sign.$digits.$separator.$decimals.'$/';

        if (!preg_match($pattern, trim($string), $matches)) {
            throw new InvalidArgumentException('The value could not be parsed as money');
        }

        $units = $matches['sign'] == '-' ? '-' : '';
        $units .= $matches['digits'];
        $units .= isset($matches['decimal1']) ? $matches['decimal1'] : '0';
        $units .= isset($matches['decimal2']) ? $matches['decimal2'] : '0';

        return (string) $units;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }

    /**
     * @param Calculator $calculator
     */
    public static function registerCalculator(Calculator $calculator)
    {
        array_unshift(self::$calculators, $calculator);
    }

    /**
     * @return Calculator
     *
     * @throws RuntimeException If cannot find calculator for money calculations
     */
    private static function initializeCalculator()
    {
        $calculators = self::$calculators;
        foreach ($calculators as $calculator) {
            /** @var Calculator $calculator */
            if ($calculator::supported()) {
                return new $calculator;
            }
        }

        throw new RuntimeException('Cannot find calculator for money calculations');
    }

    /**
     * @return Calculator
     */
    private function getCalculator()
    {
        if (self::$calculator === null) {
            self::$calculator = self::initializeCalculator();
        }

        return self::$calculator;
    }
}
