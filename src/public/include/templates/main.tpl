<?php
/**
 * @var array $page
 * @var string $page_title
 */
reset($page);
NFW::i()->registerResource('bootstrap');
// NFW::i()->registerResource('main');
?>
<!DOCTYPE html> 
<html lang="<?php echo NFW::i()->lang['lang']?>">
<head>
        <title><?php echo isset($page_title) ? $page_title : $page['title']?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="Content-Language" content="ru" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<body>

<div class="container-fluid" style="padding-top: 20px;"><?php echo $page['content']; ?></div>

</body></html>