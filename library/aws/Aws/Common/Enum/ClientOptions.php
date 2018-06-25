<?php
namespace Aws\Common\Enum;
use Aws\Common\Enum;
class ClientOptions extends Enum
{
    const KEY = 'key';
    const SECRET = 'secret';
    const TOKEN = 'token';
    const CREDENTIALS = 'credentials';
    const PROFILE = 'profile';
    const TOKEN_TTD = 'token.ttd';
    const CREDENTIALS_CACHE = 'credentials.cache';
    const CREDENTIALS_CACHE_KEY = 'credentials.cache.key';
    const CREDENTIALS_CLIENT = 'credentials.client';
    const REGION = 'region';
    const SCHEME = 'scheme';
    const SERVICE = 'service';
    const BASE_URL = 'base_url';
    const SIGNATURE = 'signature';
    const SIGNATURE_SERVICE = 'signature.service';
    const SIGNATURE_REGION = 'signature.region';
    const BACKOFF = 'client.backoff';
    const BACKOFF_RETRIES = 'client.backoff.retries';
    const BACKOFF_LOGGER = 'client.backoff.logger';
    const BACKOFF_LOGGER_TEMPLATE = 'client.backoff.logger.template';
    const SSL_CERT = 'ssl.certificate_authority';
    const SERVICE_DESCRIPTION = 'service.description';
    const MODEL_PROCESSING = 'command.model_processing';
    const VALIDATION = 'validation';
    const VERSION = 'version';
}
