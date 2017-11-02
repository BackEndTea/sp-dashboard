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

namespace Surfnet\ServiceProviderDashboard\Tests\Integration\Application\CommandHandler\Entity;

use League\Tactician\CommandBus;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\Mock;
use Surfnet\ServiceProviderDashboard\Application\CommandHandler\CommandHandler;
use Surfnet\ServiceProviderDashboard\Application\CommandHandler\Entity\CopyEntityCommandHandler;
use Surfnet\ServiceProviderDashboard\Application\Command\Entity\CopyEntityCommand;
use Surfnet\ServiceProviderDashboard\Application\Command\Entity\LoadMetadataCommand;
use Surfnet\ServiceProviderDashboard\Application\Exception\InvalidArgumentException;
use Surfnet\ServiceProviderDashboard\Domain\Entity\Entity;
use Surfnet\ServiceProviderDashboard\Domain\Entity\Service;
use Surfnet\ServiceProviderDashboard\Domain\Repository\AttributesMetadataRepository;
use Surfnet\ServiceProviderDashboard\Domain\Repository\EntityRepository;
use Surfnet\ServiceProviderDashboard\Domain\ValueObject\Attribute;
use Surfnet\ServiceProviderDashboard\Infrastructure\Manage\Client\QueryClient as ManageClient;

class CopyEntityCommandHandlerTest extends MockeryTestCase
{
    /**
     * @var CopyEntityCommandHandler
     */
    private $commandHandler;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityRepository
     */
    private $entityRepository;

    /**
     * @var ManageClient
     */
    private $manageClient;

    /**
     * @var AttributesMetadataRepository
     */
    private $attributesMetadataRepository;

    /**
     * @var Service
     */
    private $service;

    public function setUp()
    {
        parent::setUp();

        $this->commandBus = m::mock(CommandBus::class);
        $this->entityRepository = m::mock(EntityRepository::class);
        $this->manageClient = m::mock(ManageClient::class);
        $this->attributesMetadataRepository = m::mock(AttributesMetadataRepository::class);

        $this->service = new Service();
        $this->service->setTeamName('testteam');

        $this->commandHandler = new CopyEntityCommandHandler(
            $this->commandBus,
            $this->entityRepository,
            $this->manageClient,
            $this->attributesMetadataRepository
        );
    }

    /**
     * @expectedException \Surfnet\ServiceProviderDashboard\Application\Exception\InvalidArgumentException
     * @expectedExceptionMessage The id that was generated for the entity was not unique
     */
    public function test_handler_works_on_new_entities_only()
    {
        $this->entityRepository->shouldReceive('isUnique')
            ->with('dashboardid')
            ->andReturn(false);

        $this->commandHandler->handle(
            new CopyEntityCommand('dashboardid', 'manageid', $this->service)
        );
    }

    /**
     * @expectedException \Surfnet\ServiceProviderDashboard\Application\Exception\InvalidArgumentException
     * @expectedExceptionMessage Could not find entity in manage: manageid
     */
    public function test_handler_finds_remote_entity_in_manage()
    {
        $this->entityRepository->shouldReceive('isUnique')
            ->with('dashboardid')
            ->andReturn(true);

        $this->manageClient->shouldReceive('findByManageId')
            ->with('manageid')
            ->andReturn([]);

        $this->commandHandler->handle(
            new CopyEntityCommand('dashboardid', 'manageid', $this->service)
        );
    }

    /**
     * @expectedException \Surfnet\ServiceProviderDashboard\Application\Exception\InvalidArgumentException
     * @expectedExceptionMessage The entity you are about to copy does not belong to the selected team
     */
    public function test_handler_checks_access_rights_of_user()
    {
        $this->entityRepository->shouldReceive('isUnique')
            ->with('dashboardid')
            ->andReturn(true);

        $this->manageClient->shouldReceive('findByManageId')
            ->with('manageid')
            ->andReturn([
                'data' => [
                    'metaDataFields' => [
                        'coin:service_team_id' => 'wrongteam',
                    ]
                ]
            ]);

        $this->commandHandler->handle(
            new CopyEntityCommand('dashboardid', 'manageid', $this->service)
        );
    }

    public function test_handler_loads_metadata_onto_new_entity()
    {
        $this->entityRepository->shouldReceive('isUnique')
            ->with('dashboardid')
            ->andReturn(true);

        $this->manageClient->shouldReceive('findByManageId')
            ->with('manageid')
            ->andReturn([
                'data' => [
                    'metaDataFields' => [
                        'name:en' => 'name en',
                        'name:nl' => 'name nl',
                        'description:en' => 'description en',
                        'description:nl' => 'description nl',
                        'coin:service_team_id' => 'testteam',
                        'coin:attr_motivation:eduPersonTargetedID' => 'test1',
                        'coin:attr_motivation:eduPersonPrincipalName' => 'test2',
                        'coin:attr_motivation:displayName' => 'test3',
                    ]
                ]
            ]);

        $this->entityRepository->shouldReceive('save')->twice();

        $this->manageClient->shouldReceive('getMetadataXmlByManageId')
            ->with('manageid')
            ->andReturn('xml');

        $this->commandBus->shouldReceive('handle')
            ->with(m::type(LoadMetadataCommand::class))
            ->andReturn('xml');

        $entity = new Entity();

        $this->entityRepository->shouldReceive('findById')
            ->with('dashboardid')
            ->andReturn($entity);

        $this->attributesMetadataRepository->shouldReceive('findAllMotivationAttributes')
            ->andReturn(json_decode(<<<JSON
[
  {
    "id": "eduPersonTargetedIDMotivation",
    "getterName": "getEduPersonTargetedIDAttribute",
    "setterName": "setEduPersonTargetedIDAttribute",
    "friendlyName": "EduPersonTargetedIDMotivation",
    "urns": [
      "coin:attr_motivation:eduPersonTargetedID"
    ]
  },
  {
    "id": "eduPersonPrincipalNameMotivation",
    "getterName": "getPrincipleNameAttribute",
    "setterName": "setPrincipleNameAttribute",
    "friendlyName": "EduPersonPrincipalNameMotivation",
    "urns": [
      "coin:attr_motivation:eduPersonPrincipalName"
    ]
  },
  {
    "id": "displayNameMotivation",
    "getterName": "getDisplayNameAttribute",
    "setterName": "setDisplayNameAttribute",
    "friendlyName": "DisplayNameMotivation",
    "urns": [
      "coin:attr_motivation:displayName"
    ]
  }
]
JSON
            ));

        $this->commandHandler->handle(
            new CopyEntityCommand('dashboardid', 'manageid', $this->service)
        );

        $this->assertTrue($entity->getEduPersonTargetedIDAttribute()->isRequested());
        $this->assertTrue($entity->getPrincipleNameAttribute()->isRequested());
        $this->assertTrue($entity->getDisplayNameAttribute()->isRequested());
        $this->assertEquals('test1', $entity->getEduPersonTargetedIDAttribute()->getMotivation());
        $this->assertEquals('test2', $entity->getPrincipleNameAttribute()->getMotivation());
        $this->assertEquals('test3', $entity->getDisplayNameAttribute()->getMotivation());
    }
}