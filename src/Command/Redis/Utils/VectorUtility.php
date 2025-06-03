<?php

namespace Predis\Command\Redis\Utils;

class VectorUtility
{
    /**
     * Converts array of floating numbers into a blob representation
     *
     * @param array $vector
     * @param string $format Format string
     * @return string
     */
    public static function toBlob(array $vector, string $format = 'f*'): string
    {
        return pack($format, ...$vector);
    }

    /**
     * Converts blob string vector into array of floatings
     *
     * @param string $vector
     * @param string $format
     * @return array
     */
    public static function toArray(string $vector, string $format = 'f*'): array
    {
        return unpack($format, $vector);
    }
}
