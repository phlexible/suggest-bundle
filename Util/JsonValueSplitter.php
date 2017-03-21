<?php

/*
 * This file is part of the phlexible suggest package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\SuggestBundle\Util;

/**
 * Utility class for suggest fields.
 *
 * @author Phillip Look <pl@brainbits.net>
 */
class JsonValueSplitter
{
    /**
     * Split value into parts and remove duplicates.
     *
     * @param string $concatenated
     *
     * @return array
     */
    public function split($concatenated)
    {
        if (!$concatenated) {
            return array();
        }

        $splitted = @json_decode($concatenated);

        if (!$splitted || !is_array($splitted)) {
            return array();
        }

        $keys = [];

        foreach ($splitted as $key) {
            $key = trim($key);

            // skip empty values
            if (strlen($key)) {
                $keys[] = $key;
            }
        }

        $uniqueKeys = array_unique($keys);

        return $uniqueKeys;
    }
}
