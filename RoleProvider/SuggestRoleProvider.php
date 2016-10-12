<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\Bundle\SuggestBundle\RoleProvider;

use Phlexible\Bundle\GuiBundle\Security\RoleProvider\RoleProvider;

/**
 * Suggest role provider
 *
 * @author Stephan Wentz <sw@brainbits.net>
 */
class SuggestRoleProvider extends RoleProvider
{
    /**
     * {@inheritdoc}
     */
    public function provideRoles()
    {
        return [
            'ROLE_SUGGEST',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function exposeRoles()
    {
        return $this->provideRoles();
    }
}
