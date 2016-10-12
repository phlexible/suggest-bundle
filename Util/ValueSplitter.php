<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\Bundle\SuggestBundle\Util;

/**
 * Utility class for suggest fields.
 *
 * @author Phillip Look <pl@brainbits.net>
 */
class ValueSplitter
{
    /**
     * @var string
     */
    private $separatorChar;

    /**
     * @param string $separatorChar
     */
    public function __construct($separatorChar)
    {
        $this->separatorChar = $separatorChar;
    }

    /**
     * Split value into parts and remove duplicates.
     *
     * @param string $concatenated
     *
     * @return array
     */
    public function split($concatenated)
    {
        $concatenated = trim($concatenated);

        if (!$concatenated) {
            return array();
        }

        $keys = [];

        $splitted = explode($this->separatorChar, $concatenated);
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
