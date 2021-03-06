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

namespace Surfnet\ServiceProviderDashboard\Tests\Unit\Domain\ValueObject;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\ServiceProviderDashboard\Domain\ValueObject\Contact;

class ContactTest extends TestCase
{
    public function test_contact_email_address_mailto_is_stripped()
    {
        $contact = new Contact();
        $contact->setEmail('mailto:test@test.test');

        $this->assertEquals($contact->getEmail(), 'test@test.test');
    }
}
