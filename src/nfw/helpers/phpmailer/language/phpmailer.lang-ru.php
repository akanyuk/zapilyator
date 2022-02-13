<?php
/**
 * Russian PHPMailer language file: refer to English translation for definitive list
 * @package PHPMailer
 * @author Alexey Chumakov <alex@chumakov.ru>
 * @author Foster Snowhill <i18n@forstwoof.ru>
 * 
 * @var array $PHPMAILER_LANG
 */
$lang = array(
    'authenticate'         => 'Ошибка SMTP: ошибка авторизации.',
    'connect_host'         => 'Ошибка SMTP: не удается подключиться к серверу SMTP.',
    'data_not_accepted'    => 'Ошибка SMTP: данные не приняты.',
    'encoding'             => 'Неизвестный вид кодировки: ',
    'execute'              => 'Невозможно выполнить команду: ',
    'file_access'          => 'Нет доступа к файлу: ',
    'file_open'            => 'Файловая ошибка: не удается открыть файл: ',
    'from_failed'          => 'Неверный адрес отправителя: ',
    'instantiate'          => 'Невозможно запустить функцию mail.',
    'provide_address'      => 'Пожалуйста, введите хотя бы один адрес e-mail получателя.',
    'mailer_not_supported' => ' — почтовый сервер не поддерживается.',
    'recipients_failed'    => 'Ошибка SMTP: отправка по следующим адресам получателей не удалась: ',
    'empty_message'        => 'Пустое сообщение',
    'invalid_address'      => 'Не отослано, неправильный формат email адреса: ',
    'signing'              => 'Ошибка подписи: ',
    'smtp_connect_failed'  => 'Ошибка соединения с SMTP-сервером',
    'smtp_error'           => 'Ошибка SMTP-сервера: ',
    'variable_set'         => 'Невозможно установить или переустановить переменную: ',
    'extension_missing'    => 'Расширение отсутствует: ',
);

$PHPMAILER_LANG = isset($PHPMAILER_LANG) ? array_merge($PHPMAILER_LANG, $lang) : $lang;