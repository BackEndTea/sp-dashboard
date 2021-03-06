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

namespace Surfnet\ServiceProviderDashboard\Infrastructure\DashboardBundle\Mailer;

use Surfnet\ServiceProviderDashboard\Domain\Mailer\Mailer as MailerInterface;
use Surfnet\ServiceProviderDashboard\Domain\Mailer\Message as MessageInterface;
use Swift_Mailer;

/**
 * A very thin wrapper around the Swift_Mailer.
 */
class Mailer implements MailerInterface
{
    /**
     * @var Swift_Mailer
     */
    private $swiftMailer;

    public function __construct(Swift_Mailer $mailer)
    {
        $this->swiftMailer = $mailer;
    }

    public function send(MessageInterface $message)
    {
        $this->swiftMailer->send($message);
    }
}
