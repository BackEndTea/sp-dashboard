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

namespace Surfnet\ServiceProviderDashboard\Infrastructure\DashboardBundle\Validator\Constraints;

use Surfnet\ServiceProviderDashboard\Domain\ValueObject\Contact;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidContactValidator extends ConstraintValidator
{
    /**
     * @param Contact    $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (empty($value)) {
            return;
        }

        if (empty($value->getEmail()) ||
            empty($value->getFirstName()) ||
            empty($value->getLastName())
        ) {
            $this->context->addViolation($constraint->message);
            return;
        }
    }
}