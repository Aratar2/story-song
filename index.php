<?php
$successMessage = '';
$errorMessage = '';
$formData = [
    'name' => '',
    'contact' => '',
    'occasion' => '',
    'story' => '',
    'tone' => '',
];

$stories = [
    [
        'title' => 'История любви Анны и Сергея',
        'description' => 'Песня к серебряной свадьбе, где каждая строфа напоминает о первой встрече и долгой дороге вместе.',
        'youtubeId' => 'n1urLcoG0Jg',
        'tags' => ['юбилей', 'любовь'],
    ],
    [
        'title' => 'Музыкальный подарок для старшего брата',
        'description' => 'Семейное видео с архивными фотографиями и песней, которая стала теплым сюрпризом на 50-летие.',
        'youtubeId' => 'w5tWYmIOWGk',
        'tags' => ['семья', 'юбилей'],
    ],
    [
        'title' => 'Песня-история для подруги детства',
        'description' => 'Лирическая композиция о дружбе через десятилетия — с юмором и воспоминаниями о школьных годах.',
        'youtubeId' => 'J---aiyznGQ',
        'tags' => ['дружба', 'ностальгия'],
    ],
    [
        'title' => 'Серенада для мамы',
        'description' => 'Трогательная песня для маминого дня рождения: дети собрали семейные истории и пожелания.',
        'youtubeId' => 'L_jWHffIx5E',
        'tags' => ['семья', 'праздник'],
    ],
];

$steps = [
    [
        'title' => 'Расскажите историю',
        'text' => 'Опишите важные моменты: как всё начиналось, какие есть традиции, о чём мечтаете. Можно прикрепить фотографии и ссылки на видео.',
    ],
    [
        'title' => 'Выберите настроение',
        'text' => 'Спокойная баллада, душевный шансон или драйвовый рок-н-ролл — мы подберём стиль, который откликнется адресату.',
    ],
    [
        'title' => 'Получите готовую песню',
        'text' => 'Мы пришлём демо, обсудим правки и подготовим финальный трек, готовый для праздника и размещения в социальных сетях.',
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($formData as $field => $_) {
        $formData[$field] = trim($_POST[$field] ?? '');
    }

    if ($formData['contact'] === '' || $formData['story'] === '') {
        $errorMessage = 'Пожалуйста, укажите контакт и кратко опишите историю.';
    } else {
        $messageLines = [
            'Новая заявка на песню',
            'Имя: ' . ($formData['name'] !== '' ? htmlspecialchars($formData['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'не указано'),
            'Контакт: ' . htmlspecialchars($formData['contact'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'Повод: ' . ($formData['occasion'] !== '' ? htmlspecialchars($formData['occasion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'не указан'),
            'Настроение: ' . ($formData['tone'] !== '' ? htmlspecialchars($formData['tone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'не указано'),
            'История: ' . htmlspecialchars($formData['story'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ];

        $telegramToken = getenv('TELEGRAM_BOT_TOKEN');
        $chatId = getenv('TELEGRAM_CHAT_ID');

        if ($telegramToken && $chatId) {
            $payload = [
                'chat_id' => $chatId,
                'text' => implode("\n", $messageLines),
                'parse_mode' => 'HTML',
            ];

            $ch = curl_init('https://api.telegram.org/bot' . $telegramToken . '/sendMessage');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            $response = curl_exec($ch);
            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false || $httpStatus >= 400) {
                $errorMessage = 'Не удалось отправить сообщение в Telegram. Попробуйте ещё раз или свяжитесь напрямую.';
            } else {
                $successMessage = 'Спасибо! Мы получили вашу историю и свяжемся в ближайшее время.';
                $formData = array_fill_keys(array_keys($formData), '');
            }
        } else {
            $successMessage = 'Форма заполнена. Добавьте токен и чат в переменные окружения, чтобы отправлять заявки в Telegram.';
            $formData = array_fill_keys(array_keys($formData), '');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Песни на заказ — музыка для ваших историй</title>
    <link rel="stylesheet" href="assets/styles.css" />
</head>
<body>
<header class="hero">
    <div class="hero__content container">
        <span class="hero__eyebrow">Музыка для близких</span>
        <h1>Песни на заказ по вашим историям</h1>
        <p>Делаем душевные музыкальные подарки для людей, которые ценят внимание и искренность. Празднуете юбилей, свадьбу или хотите сказать «спасибо»? Мы превратим ваши воспоминания в песню.</p>
        <div class="hero__cta">
            <a class="button button--primary" href="#request">Заказать песню</a>
            <p class="hero__note">Работаем бережно с каждой историей. Аранжировки, вокал, запись, мастеринг — всё берём на себя.</p>
        </div>
    </div>
</header>

<main>
    <section class="section section--accent">
        <div class="container">
            <h2>Что мы делаем</h2>
            <div class="features">
                <article class="feature">
                    <h3>Песни для важных дат</h3>
                    <p>Юбилеи, свадьбы, годовщины, встречи выпускников. Поможем подобрать слова и музыку, которые тронут до слёз.</p>
                </article>
                <article class="feature">
                    <h3>Семейные истории</h3>
                    <p>Соберём воспоминания, озвучим тёплые слова от детей, внуков или друзей и создадим песню, которую захочется слушать снова и снова.</p>
                </article>
                <article class="feature">
                    <h3>Личное сопровождение</h3>
                    <p>Регулярно показываем промежуточные версии, помогаем с текстом и сценариями выступлений, чтобы вам было спокойно и уверенно.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section" id="stories">
        <div class="container">
            <h2>Примеры историй и песен</h2>
            <p class="section__lead">Каждый проект — это маленькая семейная хроника. Добавляйте свои ролики с YouTube: достаточно заменить идентификатор видео в списке.</p>
            <div class="story-grid">
                <?php foreach ($stories as $story): ?>
                <article class="story-card">
                    <div class="story-card__media">
                        <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($story['youtubeId'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($story['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                    </div>
                    <div class="story-card__body">
                        <h3><?php echo htmlspecialchars($story['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h3>
                        <p><?php echo htmlspecialchars($story['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                        <ul class="story-card__tags">
                            <?php foreach ($story['tags'] as $tag): ?>
                            <li>#<?php echo htmlspecialchars($tag, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section section--muted" id="process">
        <div class="container">
            <h2>Как проходит работа</h2>
            <div class="steps">
                <?php foreach ($steps as $index => $step): ?>
                <article class="step">
                    <span class="step__number">0<?php echo $index + 1; ?></span>
                    <div>
                        <h3><?php echo htmlspecialchars($step['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h3>
                        <p><?php echo htmlspecialchars($step['text'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section" id="testimonials">
        <div class="container">
            <h2>Отзывы и благодарности</h2>
            <div class="testimonials">
                <blockquote>
                    <p>«Мы заказали песню для 45-летия сестры. Все гости подпевали, а именинница слушала со слезами счастья. Спасибо за такое тёплое чудо!»</p>
                    <cite>— Светлана, Москва</cite>
                </blockquote>
                <blockquote>
                    <p>«Мама говорит, что эта песня — лучшее напоминание о нашей семье. Теперь включаем её по вечерам и вспоминаем прошлые годы».</p>
                    <cite>— Алексей, Санкт-Петербург</cite>
                </blockquote>
                <blockquote>
                    <p>«Коллективный подарок для шефа удался! Раскрыли все рабочие шутки, и при этом получилось очень по-доброму.»</p>
                    <cite>— Ирина, Нижний Новгород</cite>
                </blockquote>
            </div>
        </div>
    </section>

    <section class="section section--accent" id="request">
        <div class="container">
            <div class="request">
                <div class="request__intro">
                    <h2>Готовы написать вашу песню?</h2>
                    <p>Заполните короткую форму — и мы свяжемся в Telegram или WhatsApp, чтобы обсудить детали. Можно прикрепить ссылки на фото, видео и пожелания по стилистике.</p>
                    <ul class="request__list">
                        <li>Отвечаем в течение рабочего дня.</li>
                        <li>Обсуждаем бюджет и сроки до предоплаты.</li>
                        <li>Делаем демо и финальную версию с мастеринговой обработкой.</li>
                    </ul>
                </div>
                <form class="request__form" method="post" action="#request">
                    <?php if ($successMessage): ?>
                    <div class="alert alert--success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <?php if ($errorMessage): ?>
                    <div class="alert alert--error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <label>
                        <span>Ваше имя</span>
                        <input type="text" name="name" placeholder="Как к вам обращаться" value="<?php echo htmlspecialchars($formData['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" />
                    </label>
                    <label>
                        <span>Контакт (Telegram или WhatsApp)</span>
                        <input type="text" name="contact" placeholder="@username или номер телефона" required value="<?php echo htmlspecialchars($formData['contact'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" />
                    </label>
                    <label>
                        <span>Повод</span>
                        <input type="text" name="occasion" placeholder="Юбилей, свадьба, сюрприз для брата..." value="<?php echo htmlspecialchars($formData['occasion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" />
                    </label>
                    <label>
                        <span>Какое настроение хотите передать?</span>
                        <select name="tone">
                            <option value="" <?php echo $formData['tone'] === '' ? 'selected' : ''; ?>>Выберите</option>
                            <option value="Лирично" <?php echo $formData['tone'] === 'Лирично' ? 'selected' : ''; ?>>Лирично</option>
                            <option value="Торжественно" <?php echo $formData['tone'] === 'Торжественно' ? 'selected' : ''; ?>>Торжественно</option>
                            <option value="С юмором" <?php echo $formData['tone'] === 'С юмором' ? 'selected' : ''; ?>>С юмором</option>
                            <option value="Задорно" <?php echo $formData['tone'] === 'Задорно' ? 'selected' : ''; ?>>Задорно</option>
                        </select>
                    </label>
                    <label>
                        <span>Расскажите историю</span>
                        <textarea name="story" rows="6" placeholder="Поделитесь фактами, шутками, именами, чтобы мы почувствовали настроение" required><?php echo htmlspecialchars($formData['story'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                    </label>
                    <button class="button button--primary" type="submit">Отправить заявку</button>
                    <p class="form__note">Нажимая «Отправить», вы соглашаетесь на обработку данных для связи и подготовки песни.</p>
                </form>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="container footer__content">
        <div>
            <strong>Песни на заказ</strong>
            <p>Делаем музыку, в которой живут ваши воспоминания. Работаем по всей России и СНГ.</p>
        </div>
        <div class="footer__links">
            <a href="#stories">Примеры</a>
            <a href="#process">Как работаем</a>
            <a href="#request">Заказать песню</a>
        </div>
        <div class="footer__contacts">
            <span>Напишите нам:</span>
            <a href="https://t.me/your_profile" target="_blank" rel="noopener">Telegram</a>
            <a href="https://wa.me/70000000000" target="_blank" rel="noopener">WhatsApp</a>
        </div>
    </div>
    <p class="footer__note">© <?php echo date('Y'); ?> Песни на заказ. Все права защищены.</p>
</footer>
</body>
</html>
