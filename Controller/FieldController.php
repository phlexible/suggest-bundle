<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\Bundle\SuggestBundle\Controller;

use Phlexible\Bundle\SuggestBundle\Model\DataSourceManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Field Controller
 *
 * @author Matthias Harmuth <mharmuth@brainbits.net>
 * @Route("/datasources/field", service="phlexible_suggest.field_controller")
 */
class FieldController extends Controller
{
    /**
     * @var DataSourceManagerInterface
     */
    private $dataSourceManager;

    /**
     * DataController constructor.
     *
     * @param DataSourceManagerInterface $dataSourceManager
     */
    public function __construct(DataSourceManagerInterface $dataSourceManager)
    {
        $this->dataSourceManager = $dataSourceManager;
    }

    /**
     * Return selectfield data for lists
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @Route("/suggest", name="datasources_field_suggest")
     * @Security("is_granted('ROLE_ELEMENTS')")
     */
    public function suggestAction(Request $request)
    {
        $id = $request->get('id');
        $dsId = $request->get('ds_id');
        $language = $request->get('language');
        $query = $request->get('query', null);
        $valuesQuery = $request->get('valuesqry', '');

        $data = [];

        $source = $this->dataSourceManager->find($id);

        $filter = null;
        if ($query && $valuesQuery) {
            $filter = explode('|', $query);
        }

        foreach ($source->getActiveValuesForLanguage($language) as $key => $value) {
            if (!empty($query)) {
                if ($filter && !in_array($value, $filter)) {
                    continue;
                } elseif (!$filter && mb_stripos($value, $query) === false) {
                    continue;
                }
            }

            $data[] = ['key' => $value, 'value' => $value];
        }

        return new JsonResponse(['data' => $data]);
    }
}
