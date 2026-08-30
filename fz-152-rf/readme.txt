=== FZ-152 RF ===
Contributors: kotik
Tags: 152-фз, персональные данные, cookie, согласие, woocommerce
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cookie banner. Consent checkboxes in forms and WooCommerce. Consent logging. Tracker scanner and Metrika control. Text templates.

== Description ==

FZ-152 RF adds a cookie consent banner, consent checkboxes for WordPress and WooCommerce forms, consent logging, document templates, tracker scanning, and consent-based control of Yandex Metrica and Yandex Maps. The Pro add-on extends the plugin with third-party form integrations and control of additional trackers, maps, videos, and custom scripts.

= What the free version can do =

* Displays a customizable cookie banner on the website.
* Allows visitors to accept cookies, reject optional cookies, or change their cookie settings.
* Adds a required personal data processing consent checkbox to standard WordPress comments.
* Adds consent to WooCommerce product reviews.
* Adds consent to the WooCommerce checkout page, including both classic and block-based checkout.
* Adds consent to the WooCommerce registration page.
* Checks that required consent has been given before supported forms are submitted.
* Records received consents in the WordPress consent log.
* Stores the policy version that was in effect when consent was given.
* Allows the consent log to be exported to CSV.
* Creates the main policy and consent pages.
* Allows you to customize texts, links, the cookie banner appearance, and button styles.
* Can control when Yandex Metrica is loaded depending on the consent mode selected by the visitor.
* Can block Yandex Maps until analytics consent is given, or disable them after a visitor rejects optional cookies.
* Includes a tracker and embedded-service scanner that detects supported services working outside the plugin.
* Shows anonymous cookie-banner interaction statistics: accepted, rejected, and no choice made.

= Cookie banner =

FZ-152 RF displays a cookie banner at the bottom of the website and allows you to customize it to match your site's design.

Visitors can accept cookies, reject optional cookies, or open cookie settings. In the settings window, functional, analytics, and marketing cookies can be described separately.

In the WordPress admin area, you can change the banner text, links, colors, text sizes, button appearance, and other design options.

The plugin can also collect anonymous banner interaction statistics so the site owner can see how many visitors accepted cookies, rejected them, or made no choice.

= Yandex Metrica and cookie consent =

If Yandex Metrica is used on the website, its counter ID can be entered in the FZ-152 RF settings.

The plugin supports several counter loading modes: for example, always load it, stop loading it after a visitor rejects cookies, or load it only after explicit permission for analytics cookies.

This allows Yandex Metrica behavior to be tied to the consent scenario selected for the website.

= Trackers, services and Yandex Maps =

FZ-152 RF includes a scanner that can detect supported trackers and embedded services used on the website outside the plugin. The scanner highlights services that may need attention so the site owner can decide how they should behave depending on the visitor's cookie choice.

The free version can also control embedded Yandex Maps. Depending on the selected mode, maps can always be displayed, remain available until the visitor rejects optional cookies, or be blocked until analytics consent is given.

When a blocked map is shown after the visitor changes their decision, it can be activated immediately without reloading the page.

= Consent in WordPress and WooCommerce =

The free version is not limited to the cookie banner.

It can add required consent checkboxes to standard places where visitors may submit personal data:

* WordPress comments
* WooCommerce product reviews
* WooCommerce checkout
* WooCommerce registration page.

The text of each consent can be changed in the plugin settings. Individual built-in consent checkboxes can also be enabled or disabled when needed.

= Consent log =

Simply displaying a checkbox may not be enough if you later need to confirm that consent was actually given. This is why FZ-152 RF keeps its own consent log.

Depending on the source, the log may store:

* date and time when consent was given;
* consent source;
* the page where consent was given;
* policy version;
* consent text or consent hash;
* user or order ID, when available;
* user data available in the supported scenario;
* additional technical information required to record the event.

The consent log is available in the WordPress admin area. It can be viewed and exported to CSV.

= Policy version =

The current version of the personal data processing policy can be specified in the plugin settings.

The version is stored together with each consent record. If the policy text changes later, the log makes it possible to determine which version was in effect when a particular consent was given.

= Creating document pages =

FZ-152 RF helps you quickly prepare the main pages commonly required for websites that work with personal data and cookies.

The plugin can create the following pages:

* "Personal Data Processing Policy";
* "Consent to Personal Data Processing";
* "Cookie Policy";
* "Consent to Receive Informational and Advertising Messages".

Before creating the pages, enter your website and organization details in the plugin settings. These details are used in the prepared templates.

The generated documents are regular WordPress pages. After they are created, you can freely edit, extend, and adapt them to your particular website.

The templates are intended to speed up the initial setup and do not replace an individual legal review of your documents.

= Appearance settings =

The cookie banner can be adapted to your website design without editing theme files.

The settings include light and dark themes, background, text, link and button colors, button radius, text sizes, and other appearance options.

A preview is also available so you can evaluate changes before saving the settings.

= FZ-152 RF Pro =

FZ-152 RF Pro works together with the free FZ-152 RF plugin and extends it with integrations for third-party forms, additional trackers, maps, video embeds, and custom scripts.

Consents received through supported third-party forms use the same consent log and the same policy version as the built-in features of the free plugin.

Pro includes form integrations with:

* Contact Form 7;
* WPForms;
* Ninja Forms;
* wpDiscuz;
* Elementor Pro Forms;
* Elementor Atomic Forms;
* Forminator
* Fluent Forms
* MetForm
* Universal Forms — for simple custom HTML forms.

Pro can also manage additional services depending on the visitor's cookie choice:

* Google Analytics 4;
* Google Tag Manager;
* Digital Culture (PRO.Culture.RF / culturaltracking.ru);
* VK Ads Pixel;
* Google Maps;
* 2GIS;
* VK Video;
* RUTUBE;
* YouTube;
* Dzen.

Custom scripts can also be added manually when a ready-made integration is not available. Each supported service can use the consent mode appropriate for the website.

More integrations will be added based on user requests.

The purpose of the Pro version is not to replace the free plugin, but to extend its consent mechanisms to third-party forms and external services. If the website uses only the built-in WordPress and WooCommerce scenarios and the services available in Free, Pro is not required.

= Where data is stored =

The FZ-152 RF consent log and settings are stored in WordPress on your website's server.

The plugin does not require its own external cloud service to store the consent log and does not send consent records to such a service.

The settings page may show a one-time voluntary developer survey. The plugin does not contact the survey server until an administrator explicitly clicks the submit button. If submitted, the request body contains only the selected survey answers and the installed FZ-152 RF version. The site domain, administrator email, consent log, and visitor data are not included in the request body. Choosing "Do not participate" is stored locally and sends nothing.

= External service used by the optional survey =

Only when an administrator explicitly submits the optional survey, FZ-152 RF sends the selected answers and plugin version to the developer's server at https://kotikblog.ru/. This service is used only to aggregate survey results and is not required for any FZ-152 RF functionality.

Developer privacy policy: https://kotikblog.ru/pers-dannie-politika

If the site owner independently connects third-party services, such as Yandex Metrica, data transmission to those services is governed by the settings and policies of the respective service.

= Important =

FZ-152 RF is a technical tool for WordPress. The plugin helps implement consent collection and recording mechanisms, a cookie banner, and document preparation, but by itself it cannot guarantee that a particular website is fully compliant with all applicable legal requirements.

The required documents, consent wording, list of connected services, and personal data processing procedures depend on the particular website and the activities of the personal data operator.

Review the document texts and adapt them to your project where necessary.

== Installation ==

1. Установите FZ-152 RF через раздел «Плагины → Добавить новый» в административной панели WordPress или загрузите архив плагина вручную.
2. Активируйте плагин.
3. Откройте «Настройки → ФЗ-152».
4. Заполните данные сайта и организации, которые будут использоваться в документах.
5. Укажите версию политики обработки персональных данных.
6. Создайте в 1 клик необходимые страницы, либо укажите ссылки на уже существующие документы.
7. Настройте cookie-баннер и вкладку «Сервисы и трекеры»: Яндекс.Метрику, Яндекс.Карты и нужные режимы их работы.
8. Включите нужные чекбоксы для комментариев, отзывов WooCommerce и оформления заказа.
9. Проверьте работу форм на сайте и убедитесь, что полученные согласия появляются в журнале.

Для интеграции со сторонними конструкторами форм установите FZ-152 RF Pro. Бесплатный FZ-152 RF должен оставаться установленным и активным.

== Frequently Asked Questions ==

= Гарантирует ли FZ-152 RF полное соответствие сайта требованиям 152-ФЗ? =

Нет. Плагин является техническим инструментом. Он помогает добавить механизмы получения и фиксации согласий, cookie-баннер и подготовить основные документы, но требования к конкретному сайту зависят от состава обрабатываемых данных, подключённых сервисов и деятельности оператора персональных данных.

= Что входит в бесплатную версию? =

В бесплатной версии доступны cookie-баннер и его настройки, согласия в стандартных комментариях WordPress, отзывах WooCommerce и оформлении заказа, журнал согласий, экспорт журнала в CSV, версия политики, шаблоны основных документов, создание страниц, настройки внешнего вида, управление Яндекс.Метрикой и Яндекс.Картами, сканер трекеров и сервисов, а также статистика cookie-баннера.

= Для чего нужна FZ-152 RF Pro? =

Pro расширяет бесплатный плагин интеграциями со сторонними формами и дополнительными внешними сервисами. Она подключает формы к общему журналу согласий FZ-152 RF, а также позволяет управлять загрузкой дополнительных трекеров, карт, видео и пользовательских скриптов в зависимости от выбора посетителя.

= Я в Contact Forms 7 могу и без FZ-152 RF Pro добавить обязательный чекбокс, зачем мне Pro? =

По требованию РКН вам нужно будет доказать, что пользователь дал своё согласие. По объяснениям пользователей, которые с этим столкнулись довод "Без этого чекбокса невозможно отправить свои персональные данные" их не убедит. Вы не докажете, что чекбокс в этой форме существовал на момент отправки пользователем персональных данных, а не был сделан после. Для этого плагин и ведет журнал всех событий, чтоб вы могли это доказать. Журнал не чистится автоматически, даже через год. Очистить можно только нажатием кнопки вручную. Все согласия в нём будут в наличии. Журнал согласий это своего рода "Видеорегистратор", только для сайта, а Pro версия подключает к нему другие плагины, за которыми тоже нужно следить.

= Нужна ли бесплатная версия для работы Pro? =

Да. FZ-152 RF Pro является дополнением к бесплатному плагину и использует его настройки, документы, версию политики и журнал согласий.

= Какие формы поддерживает FZ-152 RF Pro? =

На текущий момент предусмотрены интеграции с Contact Form 7, WPForms, Ninja Forms, wpDiscuz, Elementor Pro Forms, Elementor Atomic Forms и Universal Forms для простых пользовательских HTML-форм.

= Какими дополнительными сервисами умеет управлять Pro? =

На текущий момент в Pro предусмотрено управление Google Analytics 4, Google Tag Manager, счётчиком «Цифровая культура» (PRO.Культура.РФ), VK Ads Pixel, Google Maps, 2GIS, VK Video, RUTUBE, YouTube и Dzen. Также можно добавлять пользовательские скрипты для сервисов, для которых отдельной интеграции пока нет.

= Нужна ли Pro-версия для WooCommerce? =

Нет, если речь идёт о стандартных возможностях WooCommerce. Согласие в отзывах о товарах и на странице оформления заказа входит в бесплатную версию FZ-152 RF.

= Поддерживается ли блочное оформление заказа WooCommerce? =

Да. Бесплатная версия поддерживает как классический checkout WooCommerce, так и современный блочный checkout.

= Где хранятся записи о согласиях? =

Записи хранятся в базе данных WordPress на вашем сайте и отображаются в журнале согласий в административной панели.

= Отправляет ли FZ-152 RF журнал согласий на сторонний сервер? =

Нет. Для хранения журнала собственный внешний сервис не используется. Записи остаются только в базе данных вашего сайта.

= Отправляет ли плагин что-либо разработчику из опроса в настройках? =

Только если администратор сам нажмёт кнопку «Отправить ответ». В этом случае тело запроса содержит выбранные ответы и версию FZ-152 RF. Домен сайта, email, журнал согласий и данные посетителей в тело запроса не добавляются. До отправки ответа внешнего запроса к серверу опроса нет. Кнопка «Не хочу участвовать» сохраняет отказ только локально.

= Какие данные записываются в журнал? =

Состав записи зависит от источника. Плагин фиксирует факт согласия и связанную с ним техническую информацию: дату и время, источник, страницу, версию политики и другие доступные данные, необходимые для идентификации события. В поддерживаемых сценариях могут также сохраняться данные пользователя, связанные с отправкой формы или заказом.

= Зачем нужна версия политики? =

Политика обработки персональных данных со временем может изменяться. Сохранённая версия позволяет определить, с какой редакцией документа пользователь соглашался в конкретный момент.

= Можно ли изменить текст согласия? =

Да. Тексты встроенных согласий и cookie-баннера настраиваются в административной панели плагина.

= Можно ли отключить чекбокс только в одном месте? =

Да. Встроенные согласия для комментариев, отзывов WooCommerce и оформления заказа имеют отдельные настройки и могут включаться или отключаться независимо друг от друга.

= Можно ли редактировать созданные плагином документы? =

Да. После создания это обычные страницы WordPress. Их можно редактировать в стандартном редакторе так же, как любые другие страницы сайта.

= Нужно ли создавать документы заново после каждого обновления плагина? =

Нет. Созданные страницы принадлежат вашему сайту и могут редактироваться независимо от шаблонов, поставляемых вместе с плагином.

= Обязательно ли использовать шаблоны документов из плагина? =

Нет. Можно использовать собственные документы и просто указать ссылки на них в настройках FZ-152 RF.

= Обязательно ли использовать Яндекс.Метрику? =

Нет. Интеграция с Яндекс.Метрикой является дополнительной возможностью. Если номер счётчика не указан, плагин не использует эту функцию.

= Можно ли использовать FZ-152 RF без WooCommerce? =

Да. WooCommerce не является обязательной зависимостью. На обычном сайте WordPress можно использовать cookie-баннер, документы, журнал и согласия в стандартных комментариях.

= Чем cookie-согласие отличается от согласия на обработку персональных данных? =

Это разные механизмы. Cookie-баннер управляет выбором пользователя в отношении cookie и связанных категорий. Чекбоксы в формах используются для явного подтверждения согласия при передаче персональных данных через соответствующую форму.

== Screenshots ==
1. Plugin setup instructions.
2. Footer banner text and settings for policy and agreement texts.
3. Checkbox settings for comments, reviews, and the checkout page.
4. Appearance of the footer banner (can be customized on the settings page).

== Changelog ==

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