<?php

/*
 * This file is part of the phlexible suggest package.
 *
 * (c) Stephan Wentz <sw@brainbits.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phlexible\Bundle\SuggestBundle\Controller;

use Phlexible\Bundle\GuiBundle\Response\ResultResponse;
use Phlexible\Bundle\SuggestBundle\Entity\DataSource;
use Phlexible\Bundle\SuggestBundle\Model\DataSourceManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Data sources controller.
 *
 * @author Stephan Wentz <sw@brainbits.net>
 * @Route("/datasources", service="phlexible_suggest.data_sources_controller")
 * @Security("is_granted('ROLE_SUGGEST')")
 */
class DataSourcesController
{
    /**
     * @var DataSourceManagerInterface
     */
    private $dataSourceManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param DataSourceManagerInterface $dataSourceManager
     * @param TokenStorageInterface       $tokenStorage
     */
    public function __construct(DataSourceManagerInterface $dataSourceManager, TokenStorageInterface $tokenStorage)
    {
        $this->dataSourceManager = $dataSourceManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Return something.
     *
     * @return JsonResponse
     * @Route("/list", name="suggest_datasources_list")
     */
    public function listAction()
    {
        $dataSources = $this->dataSourceManager->findBy([]);

        $sources = [];
        foreach ($dataSources as $dataSource) {
            $sources[] = [
                'id' => $dataSource->getId(),
                'title' => $dataSource->getTitle(),
            ];
        }

        return new JsonResponse(['datasources' => $sources]);
    }

    /**
     * Return something.
     *
     * @param Request $request
     *
     * @return ResultResponse
     * @Route("/create", name="suggest_datasources_create")
     */
    public function createAction(Request $request)
    {
        $title = $request->get('title');

        $dataSource = new DataSource();
        $dataSource
            ->setTitle($title)
            ->setCreatedAt(new \DateTime())
            ->setCreateUserId($this->tokenStorage->getToken()->getUser()->getId())
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
     * @param Request $request
     *
     * @return JsonResponse
     * @Route("/values", name="suggest_datasources_values")
     */
    public function valuesAction(Request $request)
    {
        $sourceId = $request->get('source_id');
        $language = $request->get('language', 'en');

        $dataSource = $this->dataSourceManager->find($sourceId);
        $keys = $dataSource->getValuesForLanguage($language);

        $data = [];
        foreach ($keys as $key) {
            if (!$key) {
                continue;
            }

            $data[] = ['key' => $key, 'value' => $key];
        }

        return new JsonResponse(['values' => $data]);
    }
}
