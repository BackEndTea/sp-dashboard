<?php

/**
 * Copyright 2017 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\ServiceProviderDashboard\Infrastructure\DashboardBundle\Controller;

use League\Tactician\CommandBus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Surfnet\ServiceProviderDashboard\Application\Command\Entity\CreateEntityCommand;
use Surfnet\ServiceProviderDashboard\Application\Command\Entity\DeleteEntityCommand;
use Surfnet\ServiceProviderDashboard\Application\Command\Entity\LoadMetadataCommand;
use Surfnet\ServiceProviderDashboard\Application\Command\Entity\PublishEntityCommand;
use Surfnet\ServiceProviderDashboard\Application\Exception\InvalidArgumentException;
use Surfnet\ServiceProviderDashboard\Application\Service\EntityService;
use Surfnet\ServiceProviderDashboard\Application\Service\ServiceService;
use Surfnet\ServiceProviderDashboard\Application\Service\TicketService;
use Surfnet\ServiceProviderDashboard\Domain\Entity\Entity;
use Surfnet\ServiceProviderDashboard\Infrastructure\DashboardBundle\Form\Entity\EditEntityType;
use Surfnet\ServiceProviderDashboard\Infrastructure\DashboardBundle\Service\AuthorizationService;
use Surfnet\ServiceProviderDashboard\Legacy\Metadata\Exception\MetadataFetchException;
use Surfnet\ServiceProviderDashboard\Legacy\Metadata\Exception\ParserException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EntityController extends Controller
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityService
     */
    private $entityService;

    /**
     * @var ServiceService
     */
    private $serviceService;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @var TicketService
     */
    private $ticketService;

    /**
     * @param CommandBus $commandBus
     * @param EntityService $entityService
     * @param ServiceService $serviceService
     * @param AuthorizationService $authorizationService
     * @param \Surfnet\ServiceProviderDashboard\Application\Service\TicketService $ticketService
     */
    public function __construct(
        CommandBus $commandBus,
        EntityService $entityService,
        ServiceService $serviceService,
        AuthorizationService $authorizationService,
        TicketService $ticketService
    ) {
        $this->commandBus = $commandBus;
        $this->entityService = $entityService;
        $this->serviceService = $serviceService;
        $this->authorizationService = $authorizationService;
        $this->ticketService = $ticketService;
    }

    /**
     * @Method("GET")
     * @Route("/entity/create", name="entity_add")
     * @Security("has_role('ROLE_USER')")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createAction()
    {
        $service = $this->serviceService->getServiceById(
            $this->authorizationService->getActiveServiceId()
        );

        $entityId = $this->entityService->createEntityUuid();
        $ticketNumber = $this->ticketService->getTicketIdForService($entityId, $service);
        if (is_null($service)) {
            $this->get('logger')->error('Unable to find selected entity while creating a new entity');
            // Todo: show error page?
        }

        $command = new CreateEntityCommand($entityId, $service, $ticketNumber);
        $this->commandBus->handle($command);

        return $this->redirectToRoute('entity_edit', ['id' => $entityId]);
    }

    /**
     * @Method({"GET", "POST"})
     * @ParamConverter("entity", class="SurfnetServiceProviderDashboard:Entity")
     * @Route("/entity/edit/{id}", name="entity_edit")
     * @Security("token.hasAccessToEntity(request.get('entity'))")
     *
     * @param Request $request
     * @param Entity $entity
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request, Entity $entity)
    {
        $flashBag = $this->get('session')->getFlashBag();
        $flashBag->clear();

        $command = $this->entityService->buildEditEntityCommand($entity);

        $form = $this->createForm(EditEntityType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            try {
                switch ($form->getClickedButton()->getName()) {
                    case 'importButton':
                        // Handle an import action based on the posted xml or import url.
                        $metadataCommand = new LoadMetadataCommand($command);
                        $this->commandBus->handle($metadataCommand);
                        return $this->redirectToRoute('entity_edit', ['id' => $entity->getId()]);
                        break;
                    case 'publishButton':
                        // Only trigger form validation on publish
                        if ($form->isValid()) {
                            $metadataCommand = new PublishEntityCommand($entity->getId());
                            $this->commandBus->handle($metadataCommand);

                            if (!$flashBag->has('error')) {
                                $this->get('session')->set('published.entity.clone', clone $entity);

                                $deleteCommand = new DeleteEntityCommand($entity->getId());
                                $this->commandBus->handle($deleteCommand);

                                return $this->redirectToRoute('service_published');
                            }
                        }
                        break;
                    default:
                        $this->commandBus->handle($command);
                        return $this->redirectToRoute('entity_list');
                        break;
                }
            } catch (MetadataFetchException $e) {
                $this->addFlash('error', 'entity.edit.metadata.fetch.exception');
            } catch (ParserException $e) {
                $this->addFlash('error', 'entity.edit.metadata.parse.exception');
            } catch (InvalidArgumentException $e) {
                $this->addFlash('error', 'entity.edit.metadata.invalid.exception');
            }
        }

        return $this->render('DashboardBundle:Entity:edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Method("GET")
     * @Route("/service/published", name="service_published")
     * @Security("has_role('ROLE_USER')")
     * @Template()
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function publishedAction()
    {
        /** @var Entity $entity */
        $entity = $this->get('session')->get('published.entity.clone');
        return $this->render('DashboardBundle:Entity:published.html.twig', ['entityName' => $entity->getNameEn()]);
    }
}
