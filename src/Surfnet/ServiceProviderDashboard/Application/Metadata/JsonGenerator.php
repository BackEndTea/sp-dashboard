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

namespace Surfnet\ServiceProviderDashboard\Application\Metadata;

use Surfnet\ServiceProviderDashboard\Application\Metadata\JsonGenerator\ArpGenerator;
use Surfnet\ServiceProviderDashboard\Application\Metadata\JsonGenerator\MotivationMetadataGenerator;
use Surfnet\ServiceProviderDashboard\Application\Metadata\JsonGenerator\PrivacyQuestionsMetadataGenerator;
use Surfnet\ServiceProviderDashboard\Application\Metadata\JsonGenerator\SpDashboardMetadataGenerator;
use Surfnet\ServiceProviderDashboard\Application\Metadata\GeneratorInterface;
use Surfnet\ServiceProviderDashboard\Domain\Entity\Entity;
use Surfnet\ServiceProviderDashboard\Domain\ValueObject\Attribute;
use Surfnet\ServiceProviderDashboard\Domain\ValueObject\Contact;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ElseExpression)
 */
class JsonGenerator implements GeneratorInterface
{

    /**
     * @var ArpGenerator
     */
    private $arpMetadataGenerator;

    /**
     * @var PrivacyQuestionsMetadataGenerator
     */
    private $privacyQuestionsMetadataGenerator;

    /**
     * @var MotivationMetadataGenerator
     */
    private $motivationMetadataGenerator;

    /**
     * @var SpDashboardMetadataGenerator
     */
    private $spDashboardMetadataGenerator;

    public function __construct(
        ArpGenerator $arpMetadataGenerator,
        PrivacyQuestionsMetadataGenerator $privacyQuestionsMetadataGenerator,
        MotivationMetadataGenerator $motivationMetadataGenerator,
        SpDashboardMetadataGenerator $spDashboardMetadataGenerator
    ) {
        $this->arpMetadataGenerator = $arpMetadataGenerator;
        $this->privacyQuestionsMetadataGenerator = $privacyQuestionsMetadataGenerator;
        $this->motivationMetadataGenerator = $motivationMetadataGenerator;
        $this->spDashboardMetadataGenerator = $spDashboardMetadataGenerator;
    }

    /**
     * @param Entity $entity
     * @return array
     */
    public function generate(Entity $entity)
    {
        $metadata = [
            'active'          => true,
            'allowedEntities' => [],
            'allowedall'      => true,
            'arp'             => $this->arpMetadataGenerator->build($entity),
            'entityid'        => $entity->getEntityId(),
            'metaDataFields'  => $this->generateMetadataFields($entity),
            'metadataurl'     => $entity->getMetadataUrl(),
            'state'           => 'testaccepted',
            'type'            => 'saml20-sp',
        ];

        if ($entity->hasComments()) {
            $metadata['revisionnote'] = $entity->getComments();
        }

        return $metadata;
    }

    /**
     * @param Entity $entity
     * @return array
     */
    private function generateMetadataFields(Entity $entity)
    {
        $metadata = array_merge(
            [
                'AssertionConsumerService:0:Binding' => $entity->getAcsBinding(),
                'AssertionConsumerService:0:Location' => $entity->getAcsLocation(),
                'NameIDFormat' => $entity->getNameIdFormat(),
                'description:en' => $entity->getDescriptionEn(),
                'description:nl' => $entity->getDescriptionNl(),
                'name:en' => $entity->getNameEn(),
                'name:nl' => $entity->getNameNl(),
            ],
            $this->generateAllContactsMetadata($entity),
            $this->generateOrganizationMetadata($entity),
            $this->generateNameIdFormatsMetadata($entity),
            $this->motivationMetadataGenerator->build($entity),
            $this->privacyQuestionsMetadataGenerator->build($entity),
            $this->spDashboardMetadataGenerator->build($entity)
        );

        if (!empty($entity->getCertificate())) {
            $metadata['certData'] = $this->stripCertificateEnvelope(
                $entity->getCertificate()
            );
        }

        if (!empty($entity->getLogoUrl())) {
            $metadata += $this->generateLogoMetadata($entity);
        }

        return $metadata;
    }

    /**
     * Strip header and footer from certificate data.
     *
     * @param string $certData
     * @return string
     */
    private function stripCertificateEnvelope($certData)
    {
        $certData = str_replace('-----BEGIN CERTIFICATE-----', '', $certData);
        $certData = str_replace('-----END CERTIFICATE-----', '', $certData);

        return trim($certData);
    }

    /**
     * @param Entity $entity
     * @return array
     */
    private function generateAllContactsMetadata(Entity $entity)
    {
        $metadata = [];
        $index = 0;

        if ($entity->getSupportContact()) {
            $metadata += $this->generateContactMetadata('support', $index++, $entity->getSupportContact());
        }

        if ($entity->getAdministrativeContact()) {
            $metadata += $this->generateContactMetadata('administrative', $index++, $entity->getAdministrativeContact());
        }

        if ($entity->getTechnicalContact()) {
            $metadata += $this->generateContactMetadata('technical', $index++, $entity->getTechnicalContact());
        }

        return $metadata;
    }

    /**
     * @param Entity $entity
     * @return array
     */
    private function generateOrganizationMetadata(Entity $entity)
    {
        $metadata = [
            'OrganizationName:en' => $entity->getOrganizationNameEn(),
            'OrganizationDisplayName:en' => $entity->getOrganizationDisplayNameEn(),
            'OrganizationURL:en' => $entity->getOrganizationUrlEn(),
            'OrganizationName:nl' => $entity->getOrganizationNameNl(),
            'OrganizationDisplayName:nl' => $entity->getOrganizationDisplayNameNl(),
            'OrganizationURL:nl' => $entity->getOrganizationUrlNl(),
        ];

        return array_filter($metadata);
    }

    /**
     * @param Entity $entity
     * @return array
     */
    private function generateNameIdFormatsMetadata(Entity $entity)
    {
        $metadata = [
            'NameIDFormats:0' => $entity->getNameIdFormats0(),
            'NameIDFormats:1' => $entity->getNameIdFormats1(),
        ];

        return array_filter($metadata);
    }

    /**
     * @param string $contactType
     * @param int $index
     * @param Contact $contact
     * @return array
     */
    private function generateContactMetadata($contactType, $index, Contact $contact)
    {
        $metadata = [
            sprintf('contacts:%d:contactType', $index) => $contactType,
        ];

        if (!empty($contact->getFirstName())) {
            $metadata[
                sprintf('contacts:%d:givenName', $index)
            ] = $contact->getFirstName();
        }

        if (!empty($contact->getLastName())) {
            $metadata[
                sprintf('contacts:%d:surName', $index)
            ] = $contact->getLastName();
        }

        if (!empty($contact->getEmail())) {
            $metadata[
                sprintf('contacts:%d:emailAddress', $index)
            ] = $contact->getEmail();
        }

        if (!empty($contact->getPhone())) {
            $metadata[
                sprintf('contacts:%d:telephoneNumber', $index)
            ] = $contact->getPhone();
        }

        return $metadata;
    }

    /**
     * Generate logo metadata fields.
     *
     * Logo dimensions are required in the SAML spec. They are always present,
     * except when the user just created the entity in the interface. We
     * determine the dimensions in those situations.
     *
     * @param Entity $entity
     * @return array
     */
    private function generateLogoMetadata(Entity $entity)
    {
        $metadata = [
            'logo:0:url' => $entity->getLogoUrl(),
        ];

        $logoData = @getimagesize(
            $entity->getLogoUrl()
        );

        if ($logoData !== false) {
            list($width, $height) = $logoData;
        } else {
            $width = 50;
            $height = 50;
        }

        $metadata['logo:0:width'] = (string) $width;
        $metadata['logo:0:height'] = (string) $height;

        return $metadata;
    }
}
