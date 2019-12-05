<?php
if (!defined('MODX_BASE_PATH')) {
    die('HACK???');
}
$out = '';
$action = !empty($action) ? $action : 'info';
$cart = ci()->carts->getCart('products');
include_once(MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php');
$parser = \DLTemplate::getInstance($modx);

switch ($action) {
    case 'info':
        $tpl = !empty($tpl) ? $tpl : '@CODE:-[+amount+] [+unit+]';
        $items = $cart->getItems();
        if (!empty($row) && !empty($items[$row]['meta']['CommerceDiscounts'])) {
            $out = $parser->parseChunk($tpl, $items[$row]['meta']['CommerceDiscounts']);
        }
        break;
    default:
        break;
}

return $out;