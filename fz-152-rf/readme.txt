=== FZ-152 RF ===
Contributors: kotik
Tags: 152-фз, персональные данные, cookie, согласие, woocommerce
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cookie banner. Consent checkboxes in forms and WooCommerce. Consent logging. Tracker scanner and Metrika control. Text templates.

== Description ==

FZ-152 RF helps WordPress website owners handle the technical side of personal data consent and cookie management.

The plugin adds a cookie consent banner, consent checkboxes for standard WordPress and WooCommerce forms, a consent log, policy and consent page templates, website diagnostics, and consent-based control of Yandex Metrica and Yandex Maps.

You do not need to understand how WordPress works internally. Fill in the website owner's details, enable the features you need, create the required pages, and open the Diagnostics tab. The plugin will check supported parts of the website and show what may require attention.


= What the free version can do =

* Displays a customizable cookie consent banner.
* Allows visitors to accept or reject optional cookies and change their cookie settings.
* Adds personal data processing consent to standard WordPress comments.
* Adds consent to WooCommerce product reviews.
* Adds consent to WooCommerce checkout.
* Adds consent to WooCommerce registration.
* Supports both classic WooCommerce checkout and Checkout Blocks.
* Records supported consent events in a consent log.
* Stores the policy version together with consent records.
* Allows the consent log to be exported to CSV.
* Creates the main policy and consent pages with one click.
* Allows existing policy pages to be used instead of creating new ones.
* Controls Yandex Metrica according to the visitor's cookie choice.
* Controls supported Yandex Maps embeds according to the visitor's cookie choice.
* Scans the website for supported forms, trackers, maps and other external services.
* Checks whether supported detected services are reflected in policy texts managed by the plugin.
* Detects signs of possible personal data transfer to some CRM and accounting systems.
* Shows cookie-banner statistics.
* Allows the cookie banner appearance to be customized.
* Includes a Custom CSS section for additional appearance changes.


= Website diagnostics =

If you are not sure what needs to be configured, start with the Diagnostics tab.

FZ-152 RF checks the homepage and published WordPress pages and looks for supported forms, analytics tools, advertising pixels, maps, videos, online chat services and other external integrations.

The plugin may also detect signs that personal data can be transferred to supported CRM or accounting systems.

When something requires attention, FZ-152 RF shows a simple explanation and suggests what to check or change.

For example, the plugin may find:

* a form that collects personal data;
* an analytics counter;
* an advertising pixel;
* an embedded map;
* an external video;
* an online chat widget;
* a supported CRM integration;
* an external service that is not reflected in the current policy text.

The scanner is intended to find common technical issues that FZ-152 RF knows how to recognize. It cannot detect every possible service or every legal requirement.


= Policy and consent pages =

FZ-152 RF can create the main pages commonly used by websites that process personal data:

* Personal Data Processing Policy;
* Consent to Personal Data Processing;
* Cookie Policy;
* Consent to Receive Informational and Advertising Messages.

Before creating the pages, enter the website owner's details in the plugin settings.

INN is optional.

If the required pages already exist, you do not have to create new ones. You can simply specify links to your existing pages.

The generated pages are regular WordPress pages and can be edited in the standard WordPress editor.


= Keeping policy texts up to date =

Websites change over time.

You may add a new analytics tool, map, advertising pixel, CRM or online chat service after the original policy was created.

FZ-152 RF can compare supported services detected on the website with policy texts managed by the plugin and show when something may require attention.

The free version can manage policy text related to Yandex Metrica and Yandex Maps.

FZ-152 RF Pro extends this feature to additional supported services and systems.


= Cookie consent banner =

FZ-152 RF displays a cookie consent banner on the website.

Visitors can:

* accept optional cookies;
* reject optional cookies;
* open cookie settings;
* change their choice later.

You can change the banner text, links, colors, buttons, text sizes and other appearance settings from the WordPress admin area.


= Yandex Metrica =

If the website uses Yandex Metrica, enter the counter ID in the FZ-152 RF settings.

The plugin can use different loading modes.

For example, Yandex Metrica can:

* always load;
* stop loading after the visitor rejects optional cookies;
* load only after the visitor gives consent for analytics cookies.

If Yandex Metrica is not used on the website, this feature can simply remain disabled.


= Yandex Maps =

The free version also supports Yandex Maps.

Depending on the selected mode, a supported map can:

* always be displayed;
* remain available until optional cookies are rejected;
* remain blocked until the visitor gives the required consent.

When the visitor changes the cookie choice, the map can be enabled without requiring a page reload.


= Personal data consent checkboxes =

Cookie consent and personal data processing consent are not the same thing.

The cookie banner is used for cookie-related choices and supported external services.

A consent checkbox is used when a visitor sends personal data through a form, for example a name, phone number, email address or delivery information.

The free version supports standard WordPress and WooCommerce scenarios.

For supported third-party form plugins such as Contact Form 7, WPForms and Elementor Forms, additional integrations are available in FZ-152 RF Pro.


= Consent log =

A checkbox shows that consent can be given, but the website owner may also need a record that a supported consent event actually happened.

FZ-152 RF keeps a consent log inside WordPress.

Depending on the source, a record may contain:

* date and time;
* consent source;
* page;
* policy version;
* user or order information when available;
* information related to the recorded consent event.

The consent log is stored in the WordPress database on your website.

It can be viewed in the WordPress admin area and exported to CSV.

FZ-152 RF does not use an external cloud service to store the consent log.


= Policy version =

The current version of the Personal Data Processing Policy can be specified in the plugin settings.

The version is saved together with supported consent records.

If the policy changes later, the stored version helps determine which policy version was in effect when a particular consent was given.

Changing the policy version can also make the cookie banner appear again for visitors who made a choice for an earlier version.


= WooCommerce =

WooCommerce is not required to use FZ-152 RF.

If WooCommerce is active, the free version supports standard consent scenarios for:

* checkout;
* customer registration;
* product reviews.

Both classic WooCommerce checkout and modern Checkout Blocks are supported.


= FZ-152 RF Pro =

FZ-152 RF Pro is an add-on for the free FZ-152 RF plugin.

The free plugin must remain installed and active.

Pro is intended for websites that use third-party forms, additional analytics and advertising services, maps, videos, CRM systems, online chat widgets and other external integrations.


= Third-party forms in Pro =

Pro connects supported third-party forms to the FZ-152 RF consent system and consent log.

Supported integrations include:

* Contact Form 7;
* WPForms;
* Ninja Forms;
* Gravity Forms;
* Fluent Forms;
* Forminator;
* MetForm;
* wpDiscuz;
* Elementor Pro Forms;
* Elementor Atomic Forms;
* Universal Forms for simple custom HTML forms.

Supported forms can use the main personal data consent and additional consent options available in Pro.


= External services in Pro =

Pro can control additional supported external services according to the visitor's cookie choice.

Supported services include:

* Google Analytics 4;
* Google Tag Manager;
* VK Ads Pixel;
* Roistat;
* Digital Culture / PRO.Culture.RF;
* Google Maps;
* 2GIS;
* VK Video;
* RUTUBE;
* YouTube;
* Dzen;
* JivoSite.

Custom JavaScript services can also be configured when a dedicated integration is not available.


= Automatic policy text in Pro =

For supported services, Pro can add or update the corresponding managed text in policies created by FZ-152 RF.

Instead of searching for company details and preparing policy text manually, the website owner can select the supported service in the plugin settings and save the changes.

Managed policy text is available for supported services including:

* 2GIS;
* VK Ads Pixel;
* Roistat;
* JivoSite;
* Digital Culture / PRO.Culture.RF.

Pro can also add policy information for confirmed use of supported CRM and accounting systems:

* amoCRM;
* Bitrix24;
* RetailCRM / Simla.com;
* MoySklad.


= Do I need Pro? =

Not necessarily.

If your website uses standard WordPress or WooCommerce forms, Yandex Metrica and Yandex Maps, the free version may be enough.

Pro is useful when the website uses supported third-party forms, additional trackers, maps, videos, CRM systems, JivoSite or other external services.


= Where data is stored =

FZ-152 RF settings and consent log records are stored in the WordPress database on your website.

The plugin does not require an FZ-152 RF cloud service to store consent records.

The consent log is not sent to the developer for storage.


= Optional developer survey =

The settings page may show an optional developer survey.

Nothing is sent to the survey server until an administrator explicitly submits an answer.

If an administrator chooses to participate, the selected answers and the installed FZ-152 RF version are sent to the developer's website at https://kotikblog.ru/.

The website's consent log and visitor personal data are not sent as part of the survey.

Developer privacy policy:
https://kotikblog.ru/pers-dannie-politika


= Important =

FZ-152 RF is a technical tool for WordPress.

It helps with consent mechanisms, cookie management, consent logging, policy preparation and detection of supported website services.

The plugin cannot guarantee that a particular website or organization fully complies with every legal requirement.

The legal basis for processing personal data, internal company procedures and requirements outside the website depend on the activities of the website owner.


== Installation ==

1. В WordPress откройте «Плагины → Добавить новый».
2. Найдите FZ-152 RF, установите и активируйте плагин.
3. Откройте «Настройки → ФЗ-152».
4. Заполните данные владельца сайта. Если ИНН вам не нужен, его можно не указывать.
5. Создайте необходимые страницы одной кнопкой. Если свои политики и согласия уже есть, просто укажите ссылки на них.
6. Настройте cookie-баннер.
7. Если используете Яндекс.Метрику, укажите номер счётчика и выберите режим его работы.
8. Включите нужные чекбоксы согласия для WordPress и WooCommerce.
9. Откройте вкладку «Диагностика» и дождитесь окончания проверки.
10. Посмотрите найденные предупреждения и выполните подсказки плагина.

Если на сайте используются сторонние формы, дополнительные трекеры, карты, видео, CRM или другие поддерживаемые внешние сервисы, можно установить FZ-152 RF Pro. Бесплатный FZ-152 RF при этом должен оставаться установленным и активным.


== Frequently Asked Questions ==

= Я только что установил плагин. Что мне делать сначала? =

Откройте «Настройки → ФЗ-152».

Сначала заполните данные владельца сайта и создайте необходимые страницы. Затем настройте cookie-баннер и нужные чекбоксы.

После этого откройте вкладку «Диагностика». Плагин проверит сайт и подскажет, что ещё стоит проверить или настроить.


= Я вообще не разбираюсь в 152-ФЗ. Я смогу настроить плагин? =

Для базовой настройки — да.

Плагин сделан для обычных владельцев WordPress-сайтов: создаёт основные страницы, показывает подсказки и сам проверяет многие распространённые вещи.

Но он не знает, чем занимается ваша организация за пределами сайта, поэтому полностью заменить специалиста по персональным данным не может.


= Что вообще такое персональные данные? =

Если совсем просто — это информация, которая относится к конкретному человеку.

На обычном сайте это часто имя, телефон, email, адрес доставки и другие данные, которые посетитель вводит в форму или при оформлении заказа.


= Что такое согласие на обработку персональных данных? =

Это согласие человека на указанную обработку его персональных данных.

На сайте его часто получают с помощью отдельного чекбокса возле формы.


= Cookie-баннер и чекбокс согласия — это одно и то же? =

Нет.

Cookie-баннер относится к cookie и связанным с ними сервисам.

Чекбокс возле формы используется, когда человек передаёт через неё персональные данные, например имя, телефон или email.


= Мне нужны и cookie-баннер, и чекбоксы? =

Зависит от сайта.

Если используются cookie, аналитика, рекламные сервисы и другие соответствующие инструменты, может понадобиться cookie-баннер.

Если посетители отправляют через формы персональные данные, для таких форм может понадобиться отдельное согласие.


= Плагин сам создаст политики и согласия? =

Да.

Заполните данные владельца сайта и нажмите кнопку создания необходимых страниц.

Если у вас уже есть свои документы, создавать новые необязательно — можно указать ссылки на существующие.


= ИНН обязательно указывать? =

Нет.

Поле ИНН можно оставить пустым.


= Можно изменить текст страниц, которые создал плагин? =

Да.

После создания это обычные страницы WordPress. Их можно открыть в редакторе и изменить.


= После каждого обновления плагина нужно создавать страницы заново? =

Нет.

Обновление плагина само по себе не требует каждый раз удалять и создавать политики заново.


= Плагин сам проверяет сайт? =

Да.

Откройте вкладку «Диагностика». Проверка запускается автоматически.

Плагин ищет известные ему формы, трекеры, карты, видео, внешние сервисы и некоторые признаки передачи данных в CRM.


= Если диагностика пишет, что всё хорошо, значит со 152-ФЗ у меня точно всё в порядке? =

Нет.

Диагностика проверяет известные плагину технические вещи на сайте.

Она не может проверить всю деятельность компании, внутренние документы, работу сотрудников и всё остальное, что может относиться к персональным данным.


= Что значит «сервис не отражён в политике»? =

Например, вы добавили на сайт карту, аналитику или другой внешний сервис, а политика была создана раньше.

Плагин может заметить поддерживаемый сервис и показать, что текст политики стоит проверить.


= Бесплатная версия сама обновляет тексты политик? =

Для поддерживаемых сценариев Яндекс.Метрики и Яндекс.Карт бесплатная версия умеет поддерживать соответствующие управляемые блоки.

Для дополнительных поддерживаемых сервисов автоматическая работа с текстами доступна в Pro.


= Что такое версия политики? =

Это номер редакции вашей политики.

Например, сначала 1.0, а после существенных изменений — 1.1.

FZ-152 RF сохраняет версию вместе с поддерживаемыми записями согласия, чтобы позже было понятно, какая редакция действовала в тот момент.


= Зачем нужен журнал согласий? =

Чтобы сохранять записи о полученных согласиях.

В журнале можно увидеть дату, источник, страницу, версию политики и другие доступные данные, связанные с конкретным событием.


= Где находится журнал? =

В административной панели WordPress, внутри FZ-152 RF.


= Журнал отправляется разработчику? =

Нет.

Он хранится в базе данных вашего WordPress-сайта.


= Журнал сам удаляет старые записи? =

Нет.

Плагин не удаляет записи просто потому, что прошло несколько месяцев или лет. Очистить журнал может владелец сайта.


= Можно скачать журнал? =

Да.

Журнал можно экспортировать в CSV.


= Обязательно устанавливать WooCommerce? =

Нет.

FZ-152 RF работает и на обычном WordPress-сайте без интернет-магазина.


= А если WooCommerce установлен? =

Бесплатная версия поддерживает стандартное оформление заказа, регистрацию покупателей и отзывы о товарах.

Поддерживается как классическое оформление заказа, так и Checkout Blocks.


= Для WooCommerce обязательно покупать Pro? =

Нет.

Для стандартных возможностей WooCommerce бесплатной версии достаточно.

Pro может понадобиться, если дополнительно используются сторонние формы, сервисы или плагины, для которых предусмотрены Pro-интеграции.


= У меня Contact Form 7. Бесплатная версия добавит туда согласие? =

Нет.

Contact Form 7 — сторонний плагин форм. Готовая интеграция с ним входит в FZ-152 RF Pro.


= Но в Contact Form 7 я и сам могу добавить чекбокс. Зачем тогда Pro? =

Сам чекбокс действительно можно добавить вручную.

Pro нужен для интеграции формы с системой FZ-152 RF: проверкой согласия и общим журналом, где фиксируются поддерживаемые события.


= Какие формы поддерживает Pro? =

Сейчас поддерживаются Contact Form 7, WPForms, Ninja Forms, Gravity Forms, Fluent Forms, Forminator, MetForm, wpDiscuz, Elementor Pro Forms, Elementor Atomic Forms и простые пользовательские HTML-формы через Universal Forms.


= Pro работает без бесплатной версии? =

Нет.

FZ-152 RF Pro является дополнением. Бесплатный FZ-152 RF должен быть установлен и активирован.


= Что Pro делает с трекерами, картами и видео? =

Для поддерживаемых сервисов Pro может управлять их загрузкой в зависимости от выбора посетителя по cookie.

Например, сервис можно не загружать до получения согласия.


= Какие внешние сервисы поддерживает Pro? =

Сейчас поддерживаются Google Analytics 4, Google Tag Manager, VK Реклама Pixel, Roistat, «Цифровая культура» / PRO.Культура.РФ, Google Maps, 2ГИС, VK Видео, RUTUBE, YouTube, Дзен и JivoSite.

Также можно настроить собственные JavaScript-сервисы, для которых отдельной интеграции пока нет.


= Pro может сам добавить нужный текст в политику? =

Для поддерживаемых сервисов — да.

Вы выбираете нужный сервис в настройках и сохраняете изменения. Pro добавляет или обновляет соответствующий управляемый блок в политике, созданной FZ-152 RF.


= Зачем это нужно, если я могу написать текст сам? =

Можно написать самостоятельно.

Но тогда нужно определить, что именно делает внешний сервис, кто его владелец, какие сведения о нём нужно указать и не забыть изменить политику после подключения нового сервиса.

Для поддерживаемых интеграций Pro берёт эту работу на себя.


= Плагин умеет находить CRM? =

Для некоторых CRM и учётных систем FZ-152 RF может обнаружить признаки их использования.

Сейчас поддерживаются amoCRM, Битрикс24, RetailCRM / Simla.com и МойСклад.

При этом установленный плагин интеграции ещё не всегда означает, что персональные данные действительно передаются, поэтому в таких случаях FZ-152 RF просит владельца сайта подтвердить использование системы.


= Нужно обязательно использовать Яндекс.Метрику? =

Нет.

Если Метрики нет, соответствующие настройки просто не используются.


= Я использую Google Analytics. Это поддерживается? =

Да, управление Google Analytics 4 доступно в FZ-152 RF Pro.


= Плагин гарантирует, что меня никогда не оштрафуют? =

Нет.

FZ-152 RF помогает с технической частью сайта: cookie, согласиями, журналом, политиками и проверкой поддерживаемых сервисов.

Но ни один WordPress-плагин не может гарантировать соответствие всей деятельности организации законодательству.

== Screenshots ==
1. Plugin setup instructions.
2. Footer banner text and settings for policy and agreement texts.
3. Checkbox settings for comments, reviews, and the checkout page.
4. Appearance of the footer banner (can be customized on the settings page).

== Changelog ==

= 0.2.5 =
* Улучшено: самое важное изменение - юридические тексты существенно переработаны. Уточнены цели обработки, сроки, передача данных внешним сервисам, отзыв согласия, рекламные рассылки и другие связанные разделы. Если не писали тексты под себя с юристами - рекомендую обновить, плагин это сделает в 1 клик.
* Добавлено: проверка соответствия сторонних сервисов на сайте текстам политик. Плагин показывает, какие найденные сервисы уже отражены в политиках, а какие требуют внимания.
* Добавлено: Яндекс.Метрика и Яндекс.Карты теперь связаны с управляемыми текстами политик в бесплатной версии. При изменении настроек соответствующие разделы можно поддерживать в актуальном состоянии без ручного редактирования всей страницы.
* Добавлено: обнаружение возможной передачи персональных данных во внешние CRM и учётные системы: amoCRM, Битрикс24, RetailCRM / Simla.com и МойСклад. В Pro подтверждённые сервисы можно добавить в политику автоматически.
* Добавлено: Pro может автоматически добавлять и обновлять в созданных FZ-152 политиках тексты для 2ГИС, VK Реклама Pixel, JivoSite, Roistat и «Цифровой культуры» / PRO.Культура.РФ. Достаточно выбрать нужные сервисы и сохранить настройки.
* Добавлено: интеграция с Roistat — обнаружение на сайте, управление загрузкой в Pro и поддержка соответствующих текстов политик.
* Улучшено: раздел «Диагностика» заметно переработан. Вместо технических сообщений плагин простым языком объясняет, что найдено и что нужно сделать.
* Улучшено: сканер точнее отличает установленный плагин или упоминание сервиса от его реальной работы на сайте, поэтому стало меньше ложных срабатываний.
* Улучшено: если запись согласия при оформлении заказа временно не удалась, плагин может повторить её при последующей финализации заказа.
* Улучшено: защита от повторного логирования согласий при регистрации, комментариях и других событиях, которые WordPress или сторонние плагины могут вызвать несколько раз.
* Изменено: ИНН в реквизитах теперь необязателен. Юридические страницы корректно формируются как с ИНН, так и без него.
* Исправлено: множество ошибок и пограничных сценариев в сканере, синхронизации политик, журналировании согласий и совместной работе Free + Pro.

= 0.2.4 =
* Исправлено: на новых установках текст cookie-баннера по умолчанию теперь сразу содержит ссылки на политику cookie и политику конфиденциальности, старый стандартный текст без ссылок автоматически обновляется, пользовательские тексты не перезаписываются.
* Изменено: основные настройки переработаны в отдельные карточки по смысловым блокам. Поля и их поведение не изменились. Просто небольшой редизайн.
* Добавлено: интеграция с сервисом «Цифровая культура» платформы PRO.Культура.РФ (culturaltracking.ru).
* Добавлено: интеграция с Gravity Forms.
* Добавлено: бета версия интеграции с Jivosite. Она работает, но принцип её работы мне не нравится, возможно придумаю как это исправить, пояснения в плагине.
* Добавлено: поддержка плагина Place Order Without Payment for WooCommerce.
* Добавлено: возможность добавить чекбокс согласия на рекламную рассылку на страницу заказа.
* Добавлено: партнёрская программа, это уже скорее не по плагину изменения, а вцелом. Но яндекс проиндексирует, вы может увидите.
* Исправлено огромное количество мелочей, ошибок и недочётов, перечислять которые мне лень) спасибо за вашу обратную связь, очень помогаете развивать и улучшать плагин.

= 0.2.3 =
* Изменено: при смене версии политик в настройках плагина, посетители снова увидят баннер с куками, даже если они уже нажимали кнопки ранее, нам нужно потребовать согласие с новой версией политики.
* Изменено: при выборе режима "Включить только после принятия" ранее счётчик метрики запускался только после того, как пользователь нажмёт кнопку "Принять" и либо обновит страницу, либо перейдёт на другую. Сейчас запускается сразу же, как только нажал принять, то же самое и с яндекс картами.
* Изменено: настройки счётчиков перенесены во вкладку "Сервисы и трекеры". Некоторые сервисы будут в бесплатной версии плагина, а некоторые только в платной.
* Добавлен автоматический сканер поддерживаемых трекеров, карт и видео, которые работают на сайте в обход настроек плагина, сканирует только главную и "страницы", посты и товары нет.
* Добавлены интеграции в free: Яндекс карты.
* Добавлены интеграции в Pro: Google Analytics, Google Tag Manager, ВК Пиксель, ВК Видео, Дзен видео, Рутуб, 2ГИС, Яндекс Карты и Google maps. Добавлена возможность управлять кастомными скриптами, интеграция для которых не готова или отсутствует.
* Огромное количество незаметных изменений, плагин прям очень сильно внутренне изменился, это самое большое обновление за всю его жизнь.


= 0.2.2 =
* Добавлено: счетчики, сколько человек приняли куки, сколько отказались, сколько проигнорировали кнопки. Последние не точно. Основных ботов плагин фильтрует, но всяких хитрых нет, поэтому игнорируют кнопки не только посетители, но и боты. Нужно просто чтоб оценить эффективность баннера и текста, соотношение принявших куки и отклонивших, да и просто любопытно же ;) Так же добавлен виджет на главную в консоль, чтоб не заходить в настройки плагина.
* Добавлено: вкладка "Кастомный CSS", в котором можно указать свои стили и не лезть в код.
* Изменено: вкладки с текстами переехали в единую вкладку "Тексты". Страницы теперь создаются автоматически нажатием 1 кнопки, необходимость в текстах в настройках стала меньше, но на всякий случай оставил.

= 0.2.1 =
* Добавлено: для владельцев сетей сайтов - автоматическое создание страниц политик и согласий по нажатию на 1 кнопку. Больше не нужно вручную копировать тексты и создавать страницы. Если у вас уже есть некоторые страницы, просто добавьте на них ссылку в нужное поле, они не будут переписаны и вторая такая создана не будет.
* Добавлено: по многочисленным  просьбам - интеграция с ninja forms в Про версии.
* Сделан бэкпорт этой версии с поддержкой php 5.6 и wordpress 5.1. Почти все функции остались рабочими. У кого много старых сайтов и отсутствует желание их обновлять - обращайтесь на почту или в телегу, поделюсь архивом

= 0.2.0 =
* Добавлено: чекбокс согласия в форму регистрации Woocommerce. Если где-то ещё забыл, свяжитесь со мной и подскажите пожалуйста, это важно!
* Исправлено: номер телефона при заказе в Woocommerce не логировался.
* Добавил определение и вывод на странице настроек других плагинов, которые создают формы и собирают персональные данные, с которыми не работает бесплатная версия этого плагина.
* В про версию добавил возможность куки баннер выводить не только в виде полоски снизу, но и блоков слева или справа. Можете сделать это и сами с помощью css. Мне просто так удобнее.