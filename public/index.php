<?php

declare(strict_types=1);

use App\Service\LandingContentRepository;
use App\Service\PricingService;
use App\Service\TelegramNotifier;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

$projectRoot = dirname(__DIR__);
$autoloadPath = $projectRoot . '/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    throw new \RuntimeException('Composer autoloader not found. Run \"composer install\" before starting the application.');
}

require $autoloadPath;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pricingConfig = require $projectRoot . '/config/pricing.php';
$landingConfig = require $projectRoot . '/config/landing.php';

$pricingService = new PricingService($pricingConfig);
$contentRepository = new LandingContentRepository($landingConfig, $projectRoot);
$telegramNotifier = new TelegramNotifier(getenv('TELEGRAM_BOT_TOKEN') ?: null, getenv('TELEGRAM_CHAT_ID') ?: null);

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$twig = Twig::create($projectRoot . '/templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

$defaultFormData = [
    'name' => '',
    'contact' => '',
    'occasion' => '',
    'story' => '',
    'tone' => '',
    'story_later' => '0',
];

$redirectToRequest = static function (Request $request, Response $response): Response {
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    $landingUrl = $routeParser->urlFor('landing');

    return $response->withHeader('Location', $landingUrl . '#request')->withStatus(303);
};

$app->get('/', function (Request $request, Response $response) use ($twig, $pricingService, $contentRepository, $defaultFormData) {
    $sessionFormData = $_SESSION['form_data'] ?? null;
    $formData = is_array($sessionFormData) ? array_merge($defaultFormData, $sessionFormData) : $defaultFormData;
    unset($_SESSION['form_data']);

    $successMessage = $_SESSION['flash_success'] ?? '';
    $successMessage = is_string($successMessage) ? $successMessage : '';
    if ($successMessage !== '') {
        unset($_SESSION['flash_success']);
    }

    $errorMessage = $_SESSION['flash_error'] ?? '';
    $errorMessage = is_string($errorMessage) ? $errorMessage : '';
    if ($errorMessage !== '') {
        unset($_SESSION['flash_error']);
    }

    $pricing = $pricingService->resolvePricing($request->getServerParams(), $_SESSION);

    $stories = $contentRepository->getStories();
    $steps = $contentRepository->getSteps();

    $routeParser = RouteContext::fromRequest($request)->getRouteParser();

    return $twig->render($response, 'home.twig', [
        'formData' => $formData,
        'successMessage' => $successMessage,
        'errorMessage' => $errorMessage,
        'pricing' => $pricing,
        'stories' => $stories,
        'steps' => $steps,
        'formAction' => $routeParser->urlFor('request.submit'),
    ]);
})->setName('landing');

$app->post('/request', function (Request $request, Response $response) use ($telegramNotifier, $defaultFormData, $redirectToRequest) {
    $parsedBody = (array) ($request->getParsedBody() ?? []);
    $formData = $defaultFormData;

    foreach ($formData as $field => $_) {
        $value = $parsedBody[$field] ?? '';
        $formData[$field] = is_string($value) ? trim($value) : '';
    }

    $formData['story_later'] = $formData['story_later'] === '1' ? '1' : '0';

    $_SESSION['form_data'] = $formData;

    $contact = $formData['contact'];
    $story = $formData['story'];
    $storyLater = $formData['story_later'] === '1';

    if ($contact === '' || ($story === '' && !$storyLater)) {
        $_SESSION['flash_error'] = 'Пожалуйста, укажите контакт и кратко опишите историю или отметьте, что расскажете её позже.';

        return $redirectToRequest($request, $response);
    }

    $messageLines = [
        'Новая заявка на песню',
        'Имя: ' . ($formData['name'] !== '' ? $formData['name'] : 'не указано'),
        'Контакт: ' . $formData['contact'],
        'Повод: ' . ($formData['occasion'] !== '' ? $formData['occasion'] : 'не указан'),
        'Настроение: ' . ($formData['tone'] !== '' ? $formData['tone'] : 'не указано'),
    ];

    if ($story !== '') {
        $messageLines[] = 'История: ' . $story;
        if ($storyLater) {
            $messageLines[] = 'Комментарий: клиент хочет дополнительно рассказать историю голосовым сообщением.';
        }
    } else {
        $messageLines[] = 'История: клиент расскажет голосовым сообщением в мессенджере.';
    }

    $success = $telegramNotifier->sendMessage($messageLines);

    if (!$success) {
        $_SESSION['flash_error'] = 'Не получилось отправить заявку. Пожалуйста, напишите мне напрямую в Telegram или WhatsApp.';

        return $redirectToRequest($request, $response);
    }

    $_SESSION['flash_success'] = $storyLater
        ? 'Спасибо! Я свяжусь и вы сможете рассказать историю голосовым сообщением.'
        : 'Спасибо! История получена — я свяжусь в ближайшее время.';
    unset($_SESSION['form_data']);
    session_write_close();

    return $redirectToRequest($request, $response);
})->setName('request.submit');

$app->run();

