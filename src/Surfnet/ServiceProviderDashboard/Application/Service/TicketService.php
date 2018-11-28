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

namespace Surfnet\ServiceProviderDashboard\Application\Service;

use JiraRestApi\Issue\Issue;
use JiraRestApi\JiraException;
use JsonMapper_Exception;
use Surfnet\ServiceProviderDashboard\Domain\Entity\Service;
use Surfnet\ServiceProviderDashboard\Domain\ValueObject\Ticket;
use Surfnet\ServiceProviderDashboard\Infrastructure\Jira\Factory\IssueFieldFactory;
use Surfnet\ServiceProviderDashboard\Infrastructure\Jira\Factory\JiraServiceFactory;

class TicketService
{
    /**
     * @var JiraServiceFactory
     */
    private $serviceFactory;

    /**
     * @var IssueFieldFactory
     */
    private $issueFactory;

    public function __construct(JiraServiceFactory $serviceFactory, IssueFieldFactory $issueFactory)
    {
        $this->serviceFactory = $serviceFactory;
        $this->issueFactory = $issueFactory;
    }

    /**
     * @param $serviceId
     * @param Service $service
     * @return string|null
     */
    public function getTicketIdForService($serviceId, Service $service)
    {
        // @todo implement Jira integration
        return null;
    }

    /**
     * Create a Jira issue from a Ticket VO
     *
     * @param Ticket $ticket
     * @return Issue|object
     * @throws JiraException
     * @throws JsonMapper_Exception
     */
    public function createIssueFrom(Ticket $ticket)
    {
        $issueField = $this->issueFactory->fromTicket($ticket);
        $issueService = $this->serviceFactory->buildIssueService();
        return $issueService->create($issueField);
    }
}
