<?php

namespace Money\Calculator;

use Money\Calculator;
use Money\Money;
use Money\Number;

/**
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
final class GmpCalculator implements Calculator
{
    /**
     * {@inheritdoc}
     */
    public static function supported()
    {
        return extension_loaded('gmp');
    }

    /**
     * {@inheritdoc}
     */
    public function compare($a, $b)
    {
        return gmp_cmp($a, $b);
    }

    /**
     * {@inheritdoc}
     */
    public function add($amount, $addend)
    {
        return gmp_strval(gmp_add($amount, $addend));
    }

    /**
     * {@inheritdoc}
     */
    public function subtract($amount, $subtrahend)
    {
        return gmp_strval(gmp_sub($amount, $subtrahend));
    }

    /**
     * {@inheritdoc}
     */
    public function multiply($amount, $multiplier)
    {
        $multiplier = (string) $multiplier;
        $decimal_separator_position = strpos($multiplier, '.');

        if ($decimal_separator_position !== false) {
            $decimal_places = strlen($multiplier) - ($decimal_separator_position + 1);
            $multiplier_base = substr($multiplier, 0, $decimal_separator_position);
            if ($multiplier_base) {
                $multiplier_base .= substr($multiplier, $decimal_separator_position + 1);
            } else {
                $multiplier_base = substr($multiplier, $decimal_separator_position + 1);
            }

            $result_base = gmp_strval(gmp_mul(gmp_init($amount), gmp_init($multiplier_base)));
            $result_length = strlen($result_base);
            $result = substr($result_base, 0, $result_length - $decimal_places);
            $result .= '.'.substr($result_base, $result_length - $decimal_places);

            return $result;
        }

        return gmp_strval(gmp_mul(gmp_init($amount), gmp_init((int) $multiplier)));
    }

    /**
     * {@inheritdoc}
     */
    public function divide($amount, $divisor)
    {
        return $this->multiply($amount, 1 / $divisor);
    }

    /**
     * {@inheritdoc}
     */
    public function ceil($number)
    {
        $number = (string) $number;

        $decimalSeparatorPosition = strpos($number, '.');
        if ($decimalSeparatorPosition === false) {
            return $number;
        }

        return $this->add(substr($number, 0, $decimalSeparatorPosition), 1);
    }

    /**
     * {@inheritdoc}
     */
    public function floor($number)
    {
        $decimalSeparatorPosition = strpos($number, '.');
        if ($decimalSeparatorPosition === false) {
            return $number;
        }

        return $this->add(substr($number, 0, $decimalSeparatorPosition), 0);
    }

    /**
     * {@inheritdoc}
     */
    public function round($number, $roundingMode)
    {
        $number = new Number($number);
        if ($number->isDecimal() === false) {
            return (string) $number;
        }

        if ($number->isHalf() === false) {
            return $this->roundDigit($number);
        }

        if ($roundingMode === Money::ROUND_HALF_DOWN) {
            return $this->floor((string) $number);
        }

        if ($roundingMode === Money::ROUND_HALF_UP) {
            return $this->ceil((string) $number);
        }

        if ($roundingMode === Money::ROUND_HALF_EVEN) {
            if ($number->isCurrentEven() === true) {
                return $this->floor((string) $number);
            } else {
                return $this->ceil((string) $number);
            }
        }

        if ($roundingMode === Money::ROUND_HALF_ODD) {
            if ($number->isCurrentEven() === true) {
                return $this->ceil((string) $number);
            } else {
                return $this->floor((string) $number);
            }
        }

        throw new \InvalidArgumentException('Unknown rounding mode');
    }

    /**
     * @param $number
     *
     * @return string
     */
    private function roundDigit(Number $number)
    {
        if ($number->isCloserToNext()) {
            return $this->ceil((string) $number);
        }

        return $this->floor((string) $number);
    }

    /**
     * {@inheritdoc}
     */
    public function share($amount, $ratio, $total)
    {
        return $this->floor($this->divide($this->multiply($amount, $ratio), $total));
    }
}
