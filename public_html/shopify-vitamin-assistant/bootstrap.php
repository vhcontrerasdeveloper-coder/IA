<?php

declare(strict_types=1);

use VitaminAssistant\Env;

require_once __DIR__ . '/src/Env.php';
require_once __DIR__ . '/src/Http.php';
require_once __DIR__ . '/src/ShopifyClient.php';
require_once __DIR__ . '/src/KnowledgeBase.php';
require_once __DIR__ . '/src/GrokClient.php';
require_once __DIR__ . '/src/AssistantService.php';

Env::load(__DIR__ . '/.env');

date_default_timezone_set(Env::get('APP_TIMEZONE', 'UTC') ?? 'UTC');
