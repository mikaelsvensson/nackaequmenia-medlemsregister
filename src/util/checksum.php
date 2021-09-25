<?php

/**
 * Checksum (Luhn) methods shamelessly copied from https://github.com/xi-project/xi-algorithm
 */

/**
 * Returns the given number with luhn algorithm applied.
 *
 * For example 456 becomes 4564.
 *
 * @param integer $number
 * @return integer
 */
function checksum_generate($number)
{
    $stack = 0;
    $digits = str_split(strrev($number), 1);
    foreach ($digits as $key => $value) {
        if ($key % 2 === 0) {
            $value = array_sum(str_split($value * 2, 1));
        }
        $stack += $value;
    }
    $stack %= 10;
    if ($stack !== 0) {
        $stack -= 10;
    }
    return (int)(implode('', array_reverse($digits)) . abs($stack));
}

/**
 * Validates the given number.
 *
 * @param integer $number
 * @return boolean
 */
function checksum_validate($number)
{
    $original = substr($number, 0, strlen($number) - 1);
    return checksum_generate($original) === $number;
}
