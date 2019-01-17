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

namespace Surfnet\ServiceProviderDashboard\Application\Command\Entity;

use Surfnet\ServiceProviderDashboard\Application\Command\Command;
use Surfnet\ServiceProviderDashboard\Domain\Entity\Contact;
use Symfony\Component\Validator\Constraints as Assert;

class PublishEntityProductionCommand implements Command
{
    /**
     * @var string
     * @Assert\NotBlank
     * @Assert\Uuid
     */
    private $id;

    /**
     * @var Contact
     */
    private $applicant;

    /**
     * @param string $id
     */
    public function __construct($id, Contact $applicatant)
    {
        $this->id = $id;
        $this->applicant = $applicatant;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    public function getApplicant()
    {
        return $this->applicant;
    }
}
