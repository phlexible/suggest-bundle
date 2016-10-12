<?php
/**
 * phlexible
 *
 * @copyright 2007-2013 brainbits GmbH (http://www.brainbits.net)
 * @license   proprietary
 */

namespace Phlexible\Bundle\SuggestBundle\Controller;

use Phlexible\Bundle\SuggestBundle\Entity\DataSource;
use Phlexible\Bundle\GuiBundle\Response\ResultResponse;
use Phlexible\Bundle\SuggestBundle\Model\DataSourceManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Data controller
 *
 * @author Stephan Wentz <sw@brainbits.net>
 * @Route("/datasources", service="phlexible_suggest.data_controller")
 * @Security("is_granted('ROLE_SUGGEST')")
 */
class DataController
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
     * Return something
     *
     * @return JsonResponse
     * @Route("/list", name="datasources_list")
     */
    public function listAction()
    {
        $dataSources = $this->dataSourceManager->findBy([]);

        $sources = [];
        foreach ($dataSources as $dataSource) {
            $sources[] = [
                'id' => $dataSource->getId(),
                'title' => $dataSource->getTitle()
            ];
        }

        return new JsonResponse(['datasources' => $sources]);
    }

    /**
     * Return something
     *
     * @param Request $request
     *
     * @return ResultResponse
     * @Route("/create", name="datasources_create")
     */
    public function createAction(Request $request)
    {
        $title = $request->get('title');

        $dataSource = new DataSource();
        $dataSource
            ->setTitle($title)
            ->setCreatedAt(new \DateTime())
            ->setCreateUserId($this->getUser()->getId())
            ->setModifiedAt($dataSource->getCreatedAt())
            ->setModifyUserId($dataSource->getCreateUserId());

        try {
            $this->dataSourceManager->updateDataSource($dataSource);

            $response = new ResultResponse(true);
        } catch (\Exception $e) {
            $response = new ResultResponse(false, $e->getMessage());
        }

        return $response;
    }

    /**
     * Return something
     *
     * @param Request $request
     *
     * @return ResultResponse
     * @Route("/add", name="datasources_add")
     */
    public function addAction(Request $request)
    {
        $sourceId = $request->get('source_id');
        $key = $request->get('key');
        $language = $request->get('language', 'de');

        // load
        $source = $this->dataSourceManager->find($sourceId);

        // add new key
        $source->addValueForLanguage($key, false);

        // save
        $dataSourceManager->save($source, $this->getUser()->getId());

        return new ResultResponse(true);
    }
}