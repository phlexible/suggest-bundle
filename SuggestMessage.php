<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\Bundle\SuggestBundle;

use Phlexible\Bundle\MessageBundle\Entity\Message;

/**
 * Suggest message
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class SuggestMessage extends Message
{
    /**
     * {@inheritdoc}
     */
    public static function getDefaultChannel()
    {
        return 'suggest';
    }

    /**
     * {@inheritdoc}
     */
    public static function getDefaultRole()
    {
        return 'ROLE_SUGGEST';
    }
}
