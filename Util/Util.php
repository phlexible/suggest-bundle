<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\Bundle\SuggestBundle\Util;

use Phlexible\Bundle\SuggestBundle\Entity\DataSourceValueBag;
use Phlexible\Bundle\SuggestBundle\GarbageCollector\ValuesCollection;

/**
 * Utility class for suggest fields.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
interface Util
{
    /**
     * Fetch values.
     *
     * @param DataSourceValueBag $valueBag
     *
     * @return ValuesCollection
     */
    public function fetchValues(DataSourceValueBag $valueBag);
}
