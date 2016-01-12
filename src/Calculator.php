<?php

namespace Money;

/**
 * Calculator Interface
 *
 * @author Frederik Bosch
 */
interface Calculator
{

    /**
     * Returns whether the calculator is supported in
     * the current server environment
     *
     * @return bool
     */
    public static function supported();

    /**
     * Compare a to b
     *
     * @param string $a
     * @param string $b
     * @return int
     */
    public function compare($a, $b);

    /**
     * Add added to amount
     *
     * @param string $amount
     * @param string $addend
     * @return string
     */
    public function add($amount, $addend);

    /**
     * Subtract subtrahend from amount
     * @param string $amount
     * @param string $subtrahend
     * @return string
     */
    public function subtract($amount, $subtrahend);

    /**
     * Multiply amount with multiplier
     *
     * @param string $amount
     * @param int|float|string $multiplier
     * @return string
     */
    public function multiply($amount, $multiplier);

    /**
     * Divide amount with divisor
     *
     * @param string $amount
     * @param int|float|string $divisor
     * @return string
     */
    public function divide($amount, $divisor);

    /**
     * Round number to following integer
     *
     * @param string $number
     * @return string
     */
    public function ceil($number);

    /**
     * Round number to preceding integer
     *
     * @param string $number
     * @return string
     */
    public function floor($number);

    /**
     * Round number, use rounding mode for tie-breaker
     *
     * @param string $number
     * @param int $roundingMode
     * @return string
     */
    public function round($number, $roundingMode);

    /**
     * Share amount among ratio / total portions
     *
     * @param string $amount
     * @param int|float $ratio
     * @param int|float $total
     * @return string
     */
    public function share($amount, $ratio, $total);
}
