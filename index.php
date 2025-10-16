<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$successMessage = $_SESSION['flash_success'] ?? '';
if ($successMessage !== '') {
    unset($_SESSION['flash_success']);
}

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
        'audioFile' => 'assets/audio/anna-sergey.mp3',
        'tags' => ['юбилей', 'любовь'],
    ],
    [
        'title' => 'Музыкальный подарок для старшего брата',
        'description' => 'Семейное видео с архивными фотографиями и песней, которая стала теплым сюрпризом на 50-летие.',
        'audioFile' => 'assets/audio/big-brother.mp3',
        'tags' => ['семья', 'юбилей'],
    ],
    [
        'title' => 'Песня-история для подруги детства',
        'description' => 'Лирическая композиция о дружбе через десятилетия — с юмором и воспоминаниями о школьных годах.',
        'audioFile' => 'assets/audio/best-friend.mp3',
        'tags' => ['дружба', 'ностальгия'],
    ],
    [
        'title' => 'Серенада для мамы',
        'description' => 'Трогательная песня для маминого дня рождения: дети собрали семейные истории и пожелания.',
        'audioFile' => 'assets/audio/mom-serenade.mp3',
        'tags' => ['семья', 'праздник'],
    ],
];

$steps = [
    [
        'title' => 'Расскажите историю',
        'text' => 'Опишите важные моменты: как всё начиналось, какие есть традиции и какие слова мечтаете услышать. Можно прикрепить фотографии и ссылки на видео.',
    ],
    [
        'title' => 'Подберём настроение',
        'text' => 'Решим, какой характер подойдёт адресату: лиричная баллада, задорный рок-н-ролл или нежный поп. Подскажу с выбором, даже если не знаете, с чего начать.',
    ],
    [
        'title' => 'Получите готовую песню',
        'text' => 'Отправляю демо в течение 1–3 дней, обсуждаем нюансы и довожу трек до подарочного состояния — останется только включить в нужный момент.',
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
                'disable_web_page_preview' => true,
            ];

            $ch = curl_init('https://api.telegram.org/bot' . $telegramToken . '/sendMessage');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            $response = curl_exec($ch);
            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false) {
                error_log('Не удалось выполнить запрос к Telegram: ' . curl_error($ch));
            }

            curl_close($ch);

            $responseData = json_decode($response ?? '', true);
            $telegramOk = is_array($responseData) && ($responseData['ok'] ?? false) === true;

            if ($response === false || $httpStatus >= 400 || !$telegramOk) {
                if (isset($responseData['description'])) {
                    error_log('Ошибка Telegram API: ' . $responseData['description']);
                }

                $errorMessage = 'Не получилось отправить заявку. Пожалуйста, напишите мне напрямую в Telegram или WhatsApp.';
            } else {
                $successMessage = 'Спасибо! История получена — я свяжусь в ближайшее время.';
                $_SESSION['flash_success'] = $successMessage;
                $formData = array_fill_keys(array_keys($formData), '');
                session_write_close();
                $redirectUrl = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
                header('Location: ' . $redirectUrl);
                exit;
            }
        } else {
            error_log('TELEGRAM_BOT_TOKEN или TELEGRAM_CHAT_ID не заданы.');
            $errorMessage = 'Не получилось отправить заявку. Пожалуйста, напишите мне напрямую в Telegram или WhatsApp.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Песня на заказ для близких — 1000 ₽ (10 €) и готово за 1–3 дня</title>
    <meta name="description" content="Пишу персональные песни на заказ для юбилеев, годовщин, признаний и семейных праздников. Скидка 50%: цена 1000 ₽ (10 €) вместо 2000 ₽ (20 €), первые демо в течение 1–3 дней." />
    <meta name="keywords" content="песня на заказ, песня в подарок, музыка на юбилей, песня для любимого, песня для родителей, авторская песня" />
    <meta name="robots" content="index,follow" />
    <link rel="canonical" href="https://story-song.ru/" />
    <meta property="og:title" content="Песня на заказ за 1000 ₽ (10 €) — подарок близким" />
    <meta property="og:description" content="Расскажите историю — я превращу её в песню-сюрприз для юбилея, годовщины или признания. Первое демо пришлю уже через 1–3 дня." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://story-song.ru/" />
    <meta property="og:image" content="https://images.unsplash.com/photo-1485579149621-3123dd979885?auto=format&fit=crop&w=1600&q=80" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Песня на заказ за 1000 ₽ (10 €)" />
    <meta name="twitter:description" content="Индивидуальные песни-сюрпризы для родных и друзей. Личное сопровождение и первые демо в течение 1–3 дней." />
    <meta name="twitter:image" content="https://images.unsplash.com/photo-1485579149621-3123dd979885?auto=format&fit=crop&w=1600&q=80" />
    <link rel="stylesheet" href="assets/styles.css" />
    <!-- Meta Pixel Code -->
    <script>
        !function (f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function () {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments);
            };
            if (!f._fbq) f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s);
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '1149060356690578');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id=1149060356690578&amp;ev=PageView&amp;noscript=1" alt="" /></noscript>
    <!-- End Meta Pixel Code -->
</head>
<body>
<header class="site-header" id="top">
    <div class="container site-header__inner">
        <a class="logo" href="#top" aria-label="Песни на заказ — на главную">StorySong</a>
        <nav class="nav" aria-label="Основная навигация">
            <a href="#stories">Примеры</a>
            <a href="#process">Процесс</a>
            <a href="#testimonials">Отзывы</a>
            <a href="#faq">FAQ</a>
        </nav>
        <a class="button button--ghost" href="#request">Оставить заявку</a>
    </div>
</header>

<section class="hero">
    <div class="hero__content container">
        <div class="hero__text">
            <span class="hero__eyebrow">Подарок, который слышно сердцем</span>
            <h1>Персональные песни-сюрпризы для ваших близких</h1>
            <p>Расскажите мне о человеке, ради которого готовите праздник. Я напишу текст, подберу музыку и голос, чтобы за 1–3 дня вы получили готовый трек за <span class="price price--discount"><span class="price__old"><s>2000 ₽ (20 €)</s></span> <span class="price__new">1000 ₽ (10 €)</span></span>. Юбилей родителей, годовщина отношений или признание другу — песня сохранит ваши чувства навсегда.</p>
            <div class="hero__cta">
                <div class="hero__buttons">
                    <a class="button button--primary" href="#request">Заказать песню</a>
                    <a class="button button--contrast" href="#stories">Послушать примеры</a>
                </div>
                <ul class="hero__badges" aria-label="Преимущества сервиса">
                    <li class="badge">⏱️ Первое демо за 1–3 дня</li>
                    <li class="badge">💸 Скидка 50% — <span class="price price--discount"><span class="price__old"><s>2000 ₽ (20 €)</s></span> <span class="price__new">1000 ₽ (10 €)</span></span></li>
                    <li class="badge">💬 Помогаю, даже если не знаете, с чего начать</li>
                </ul>
            </div>
        </div>
        <div class="hero__media" aria-hidden="true">
            <div class="hero__media-card">
                <img src="https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=900&q=80" alt="Музыкант играет на гитаре в студии" loading="lazy" />
                <div class="hero__waveform">
                    <span>Ваша история</span>
                    <span>→</span>
                    <span>Авторская песня</span>
                </div>
            </div>
        </div>
    </div>
    <div class="hero__stats container" aria-label="Результаты в цифрах">
        <div class="stat">
            <strong>1–3 дня</strong>
            <span>первое демо после вашей истории</span>
        </div>
        <div class="stat">
            <strong class="price price--discount"><span class="price__old"><s>2000 ₽ (20 €)</s></span> <span class="price__new">1000 ₽ (10 €)</span></strong>
            <span>фиксированная стоимость песни</span>
        </div>
        <div class="stat">
            <strong>150+</strong>
            <span>семейных историй уже озвучено</span>
        </div>
    </div>
</section>

<main>
    <section class="section section--accent">
        <div class="container">
            <h2>Для каких моментов подходит песня</h2>
            <p class="section__lead">Я аккуратно складываю ваши воспоминания, забавные истории и важные слова в музыкальный подарок, который легко поставить на празднике или отправить в мессенджер.</p>
            <div class="features">
                <article class="feature">
                    <div class="feature__icon" aria-hidden="true">🎂</div>
                    <h3>Юбилеи и дни рождения</h3>
                    <p>Рассказываете, за что любите именинника, — и он слышит это в куплетах. Добавлю тёплые обращения от семьи и друзей.</p>
                </article>
                <article class="feature">
                    <div class="feature__icon" aria-hidden="true">💞</div>
                    <h3>Признания и годовщины</h3>
                    <p>Для предложения руки, годовщины отношений или неожиданного «люблю». Песня станет саундтреком вашей истории.</p>
                </article>
                <article class="feature">
                    <div class="feature__icon" aria-hidden="true">👪</div>
                    <h3>Слова родителям и детям</h3>
                    <p>Передайте благодарность маме, папе или поздравьте ребёнка. Использую фразы, которые дороги вашей семье.</p>
                </article>
                <article class="feature">
                    <div class="feature__icon" aria-hidden="true">🎁</div>
                    <h3>Сюрпризы друзьям</h3>
                    <p>Разделите с друзьями школьные шутки, истории путешествий и внутренние мемы — всё окажется в припеве.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section" id="offer">
        <div class="container offer">
            <div class="offer__content">
                <h2>Прозрачные условия заказа</h2>
                <p class="section__lead">Фиксированная стоимость <span class="price price--discount"><span class="price__old"><s>2000 ₽ (20 €)</s></span> <span class="price__new">1000 ₽ (10 €)</span></span> включает текст, музыку, вокал и финальный мастер. Никаких скрытых платежей — рассказываете историю, а я делаю остальное.</p>
                <ul class="offer__list">
                    <li>Помогаю сформулировать мысли, если сложно начать.</li>
                    <li>Присылаю демо в течение 1–3 дней и учитываю пожелания по правкам.</li>
                    <li>Отправляю готовый трек с текстом и подсказками, как эффектно его презентовать.</li>
                </ul>
            </div>
            <div class="offer__details" aria-label="Ключевые условия заказа песни">
                <div class="offer__tag"><span class="price price--discount"><span class="price__old"><s>2000 ₽ (20 €)</s></span> <span class="price__new">1000 ₽ (10 €)</span></span></div>
                <div class="offer__tag">1–3 дня до первого демо</div>
                <div class="offer__note">Оплата после подтверждения концепции.</div>
            </div>
        </div>
    </section>

    <section class="section" id="stories">
        <div class="container">
            <h2>Примеры историй и песен</h2>
            <p class="section__lead">Каждый проект — это маленькая семейная хроника.</p>
            <div class="story-grid">
                <?php foreach ($stories as $story): ?>
                <article class="story-card">
                    <div class="story-card__media">
                        <?php if (isset($story['audioFile']) && is_file($story['audioFile'])): ?>
                        <?php
                        $audioPath = $story['audioFile'];
                        $extension = strtolower(pathinfo($audioPath, PATHINFO_EXTENSION));
                        $mimeType = 'audio/mpeg';

                        if ($extension === 'wav') {
                            $mimeType = 'audio/wav';
                        } elseif ($extension === 'ogg') {
                            $mimeType = 'audio/ogg';
                        }
                        ?>
                        <audio controls preload="none">
                            <source src="<?php echo htmlspecialchars($audioPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" type="<?php echo htmlspecialchars($mimeType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" />
                            Ваш браузер не поддерживает воспроизведение аудио.
                        </audio>
                        <?php else: ?>
                        <div class="story-card__placeholder">
                            <p>Добавьте файл <code><?php echo htmlspecialchars($story['audioFile'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></code> в папку <code>assets/audio</code>, и здесь появится плеер.</p>
                        </div>
                        <?php endif; ?>
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

    <section class="section" id="benefits">
        <div class="container longread">
            <h2>Почему песня на заказ — лучший способ сказать о важном</h2>
            <p>Индивидуальная песня вызывает эмоции сильнее, чем стандартный подарок. Я внимательно слушаю вашу историю, подмечаю детали и любимые выражения, чтобы текст звучал по-настоящему. Подбираю стиль — от неоклассики и поп-музыки до лёгкого рока — и собираю звучание, которое приятно включить в семейном кругу.</p>
            <p>Веду вас за руку: помогаю сформулировать мысли, записываю вокал, делюсь промежуточными версиями и довожу трек до финала. Такой подарок подходит для юбилеев, признаний, свадебных церемоний, сюрпризов для родителей и лучших друзей.</p>
            <ul>
                <li>Текст, написанный вашим языком и любимыми обращениями.</li>
                <li>Музыка в стиле, который нравится вам и адресату.</li>
                <li>Готовая песня и текст для самостоятельного исполнения.</li>
                <li>Бережное отношение к личным воспоминаниям и материалам.</li>
            </ul>
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
                    <p>«Заказала мужу на годовщину — в песне прозвучали наши переписки и любимые места. Он переслушивает уже третью неделю подряд!»</p>
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
                    <p>Заполните короткую форму — я свяжусь в Telegram или WhatsApp, чтобы обсудить детали. Можно прикрепить ссылки на фото, видео и подсказать, какие моменты хочется услышать в песне.</p>
                    <ul class="request__list">
                        <li>Отвечаю в течение рабочего дня.</li>
                        <li>Стоимость фиксирована — <span class="price price--discount"><span class="price__old"><s>2000 ₽ (20 €)</s></span> <span class="price__new">1000 ₽ (10 €)</span></span>, оплата после подтверждения концепции.</li>
                        <li>Присылаю демо, собираю комментарии и довожу финальный мастер.</li>
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
                        <textarea name="story" rows="6" placeholder="Поделитесь фактами, шутками, именами, чтобы я почувствовал настроение" required><?php echo htmlspecialchars($formData['story'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                    </label>
                    <button class="button button--primary" type="submit">Отправить заявку</button>
                    <p class="form__note">Нажимая «Отправить», вы соглашаетесь на обработку данных для связи и подготовки песни.</p>
                </form>
            </div>
        </div>
    </section>

    <section class="section section--muted" id="faq">
        <div class="container">
            <h2>Ответы на популярные вопросы</h2>
            <div class="faq">
                <article class="faq__item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">Сколько времени занимает создание песни?</h3>
                    <div class="faq__content" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">Первое демо отправляю в течение 1–3 дней после того, как получу историю. Если будут запрошены правки, то время может увеличиться.</p>
                    </div>
                </article>
                <article class="faq__item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">Можно ли внести правки после демо?</h3>
                    <div class="faq__content" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">Послушаю ваши идеи и комментарии к тексту и музыке, и при необходимости внесу до двух раундов правок — чтобы песня полностью передавала то, что вы чувствуете и хотите выразить.</p>
                    </div>
                </article>
                <article class="faq__item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">Какие форматы файлов вы предоставляете?</h3>
                    <div class="faq__content" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">Передаю готовый мастер в WAV и MP3, прикладываю текст и подсказки, как эффектно презентовать трек на празднике.</p>
                    </div>
                </article>
                <article class="faq__item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 itemprop="name">Работаете ли вы с заказами из других городов?</h3>
                    <div class="faq__content" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">Да, я работаю онлайн и принимаю истории из любых городов и стран. Переписываемся в удобном мессенджере и присылаю готовые файлы в том формате, который вам нужен.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="section section--cta">
        <div class="container cta-banner">
            <div>
                <h2>Пора удивлять тех, кого вы любите</h2>
                <p>Поделитесь историей сейчас — и уже через 1–3 дня получите первое демо будущего хита. Я помогу подобрать слова и сделаю всё, чтобы песня прозвучала в нужный момент.</p>
            </div>
            <a class="button button--primary" href="#request">Оставить заявку</a>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="container footer__content">
        <div>
            <strong>Песни на заказ</strong>
            <p>Создаю музыку, в которой живут ваши воспоминания. Работаю по всей России и миру.</p>
        </div>
        <div class="footer__links">
            <a href="#stories">Примеры</a>
            <a href="#process">Как работаем</a>
            <a href="#request">Заказать песню</a>
        </div>
        <div class="footer__contacts">
            <span>Напишите мне:</span>
            <a href="https://t.me/airat_dev" target="_blank" rel="noopener">Telegram @airat_dev</a>
            <a href="https://wa.me/79274665595" target="_blank" rel="noopener">WhatsApp +7 927 466 5595</a>
        </div>
    </div>
    <p class="footer__note">© <?php echo date('Y'); ?> Песни на заказ. Все права защищены.</p>
</footer>
<div class="floating-cta" role="complementary" aria-label="Быстрая заявка на песню">
    <button class="floating-cta__close" type="button" aria-label="Скрыть предложение">×</button>
    <div class="floating-cta__content">
        <span>Есть история? Сделаю песню за 1–3 дня.</span>
        <a class="button button--primary" href="#request">Оставить заявку</a>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var floatingCta = document.querySelector('.floating-cta');
        if (!floatingCta) {
            return;
        }

        var dismissed = false;
        try {
            dismissed = window.sessionStorage.getItem('floatingCtaDismissed') === '1';
        } catch (error) {
            dismissed = false;
        }

        if (dismissed) {
            floatingCta.setAttribute('hidden', 'hidden');
            return;
        }

        var closeButton = floatingCta.querySelector('.floating-cta__close');
        if (closeButton) {
            closeButton.addEventListener('click', function () {
                floatingCta.setAttribute('hidden', 'hidden');
                try {
                    window.sessionStorage.setItem('floatingCtaDismissed', '1');
                } catch (error) {
                    /* ignore */
                }
            });
        }
    });
</script>
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "Сколько времени занимает создание песни?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Первое демо отправляю в течение 1–3 дней после получения вашей истории. Если нужны дополнительные правки или живые инструменты, заранее согласуем новые сроки."
            }
        },
        {
            "@type": "Question",
            "name": "Можно ли внести правки после демо?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Послушаю ваши идеи и комментарии к тексту и музыке, и при необходимости внесу до двух раундов правок — чтобы песня полностью передавала то, что вы чувствуете и хотите выразить."
            }
        },
        {
            "@type": "Question",
            "name": "Какие форматы файлов я получу?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Получите мастер в WAV и MP3 вместе с текстом песни и подсказками, как эффектно презентовать трек."
            }
        },
        {
            "@type": "Question",
            "name": "Работаете ли вы дистанционно?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Да, работаю онлайн: принимаю истории из любых городов, общаюсь в удобном мессенджере и отправляю файлы в нужном формате."
            }
        }
    ]
}
</script>
<!-- Top.Mail.Ru counter -->
<script type="text/javascript">
var _tmr = window._tmr || (window._tmr = []);
_tmr.push({id: "3708530", type: "pageView", start: (new Date()).getTime()});
(function (d, w, id) {
  if (d.getElementById(id)) return;
  var ts = d.createElement("script"); ts.type = "text/javascript"; ts.async = true; ts.id = id;
  ts.src = "https://top-fwz1.mail.ru/js/code.js";
  var f = function () {var s = d.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ts, s);};
  if (w.opera == "[object Opera]") { d.addEventListener("DOMContentLoaded", f, false); } else { f(); }
})(document, window, "tmr-code");
</script>
<noscript><div><img src="https://top-fwz1.mail.ru/counter?id=3708530;js=na" style="position:absolute;left:-9999px;" alt="Top.Mail.Ru" /></div></noscript>
<!-- /Top.Mail.Ru counter -->
</body>
</html>
