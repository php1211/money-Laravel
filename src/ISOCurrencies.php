<?php

/**
 * This file is part of the Money library.
 *
 * Copyright (c) 2011-2014 Mathias Verraes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Money;

/**
 * List of supported ISO 4217 currency codes and names
 *
 * @author Mathias Verraes
 */
final class ISOCurrencies extends Currencies
{
    public function __construct()
    {
        $this->currencies = require __DIR__.'/currencies.php';
    }
}
