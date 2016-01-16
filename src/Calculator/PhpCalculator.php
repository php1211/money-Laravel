<?php

namespace Money\Calculator;

use Money\Calculator;

/**
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
final class PhpCalculator implements Calculator
{
    /**
     * {@inheritdoc}
     */
    public static function supported()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function compare($a, $b)
    {
        return ($a < $b) ? -1 : (($a > $b) ? 1 : 0);
    }

    /**
     * {@inheritdoc}
     */
    public function add($amount, $addend)
    {
        $result = $amount + $addend;

        $this->assertInteger($result);

        return (string) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function subtract($amount, $subtrahend)
    {
        $result = $amount - $subtrahend;

        $this->assertInteger($result);

        return (string) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function multiply($amount, $multiplier)
    {
        $result = $amount * $multiplier;

        $this->assertIntegerBounds($result);

        return (string) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function divide($amount, $divisor)
    {
        $result = $amount / $divisor;

        $this->assertIntegerBounds($result);

        return (string) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function ceil($number)
    {
        return $this->castInteger(ceil($number));
    }

    /**
     * {@inheritdoc}
     */
    public function floor($number)
    {
        return $this->castInteger(floor($number));
    }

    /**
     * {@inheritdoc}
     */
    public function round($number, $roundingMode)
    {
        return $this->castInteger(round($number, 0, $roundingMode));
    }

    /**
     * {@inheritdoc}
     */
    public function share($amount, $ratio, $total)
    {
        return $this->castInteger(floor($amount * $ratio / $total));
    }

    /**
     * Asserts that an integer value didn't become something else
     * (after some arithmetic operation)
     *
     * @param int $amount
     *
     * @throws \OverflowException If integer overflow occured
     * @throws \UnderflowException If integer underflow occured
     */
    private function assertIntegerBounds($amount)
    {
        if ($amount > PHP_INT_MAX) {
            throw new \OverflowException;
        } elseif ($amount < ~PHP_INT_MAX) {
            throw new \UnderflowException;
        }
    }

    /**
     * Casts an amount to integer ensuring that an overflow/underflow did not occur
     *
     * @param int $amount
     *
     * @return int
     */
    private function castInteger($amount)
    {
        $this->assertIntegerBounds($amount);

        return (string) intval($amount);
    }

    /**
     * Asserts that integer remains integer after arithmetic operations
     *
     * @param int $amount
     */
    private function assertInteger($amount)
    {
        if (filter_var($amount, FILTER_VALIDATE_INT) === false) {
            throw new \UnexpectedValueException('The result of arithmetic operation is not an integer');
        }
    }

}
