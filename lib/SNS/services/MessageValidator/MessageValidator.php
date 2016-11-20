<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */
w3_require_once(W3TC_LIB_DIR . '/SNS/services/MessageValidator/sns-exceptions.php');

/**
 * This class uses openssl to verify SNS messages to ensure that they were sent by AWS.
 */
class MessageValidator {

    private $hostPattern
        = '/^sns\.[a-zA-Z0-9\-]{3,}\.amazonaws\.com(\.cn)?$/';

    /**
     * Constructs the Message Validator object and ensures that openssl is installed
     *
     * @throws Exception If openssl is not installed
     */
    public function __construct()
    {
        if (!extension_loaded('openssl')) {
            throw new Exception('The openssl extension is required to use the SNS Message '
                . 'Validator. Please install this extension in order to use this feature.');
        }
    }

    /**
     * Validates a message from SNS to ensure that it was delivered by AWS
     *
     * @param Message $message The message to validate
     *
     * @throws CannotGetPublicKeyFromCertificateException If the certificate cannot be retrieved
     * @throws CertificateFromUnrecognizedSourceException If the certificate's source cannot be verified
     * @throws InvalidMessageSignatureException           If the message's signature is invalid
     */
    public function validate($message) {
        // Get the cert's URL and ensure it is from AWS
        $certUrl = $message->get('SigningCertURL');
        $this->validateUrl($certUrl);

        // Get the cert itself and extract the public key
        $response = wp_remote_get($certUrl);
        if (is_wp_error($response))
            throw new CannotGetPublicKeyFromCertificateException('Could not retrieve certificate from ' . $certUrl);

        $certificate = wp_remote_retrieve_body($response);
        $publicKey = openssl_get_publickey($certificate);
        if (!$publicKey) {
            throw new CannotGetPublicKeyFromCertificateException('Could not extract public key from ' . $certUrl);
        }

        // Verify the signature of the message
        $stringToSign = $message->getStringToSign();
        $incomingSignature = base64_decode($message->get('Signature'));
        if (!openssl_verify($stringToSign, $incomingSignature, $publicKey, OPENSSL_ALGO_SHA1)) {
            throw new InvalidMessageSignatureException('The message did not match the signature ' . "\n" . $stringToSign);
        }
    }

    /**
     * Ensures that the URL of the certificate is one belonging to AWS, and not
     * just something from the amazonaws domain, which could include S3 buckets.
     *
     * @param string $url Certificate URL
     *
     * @throws InvalidSnsMessageException if the cert url is invalid.
     */
    private function validateUrl($url)
    {
        $parsed = parse_url($url);
        if (empty($parsed['scheme'])
            || empty($parsed['host'])
            || $parsed['scheme'] !== 'https'
            || substr($url, -4) !== '.pem'
            || !preg_match($this->hostPattern, $parsed['host'])
        ) {
            throw new InvalidSnsMessageException(
                'The certificate is located on an invalid domain.'
            );
        }
    }
    /**
     * Determines if a message is valid and that is was delivered by AWS. This method does not throw exceptions and
     * returns a simple boolean value.
     *
     * @param Message $message The message to validate
     * @return bool
     */
    public function isValid($message)
    {
        try {
            $this->validate($message);
            return true;
        } catch (SnsMessageValidatorException $e) {
            $error = $e->getMessage();
            return false;
        }
    }
}
