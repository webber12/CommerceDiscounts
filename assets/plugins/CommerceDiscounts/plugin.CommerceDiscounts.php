<?php
if (!defined('MODX_BASE_PATH')) {
    die('HACK???');
}

$e = $modx->event;
$events = ['OnWebPageInit', 'OnCollectSubtotals', 'OnOrderSaved', 'OnBeforeCartItemAdding', 'OnBeforeCartItemUpdating', 'OnPageNotFound'];
if (in_array($e->name, $events)) {
    $controllerName = !empty(trim($params['controller_name'])) ? trim($params['controller_name']) : 'CommerceDiscountsController';
    if (!file_exists(MODX_BASE_PATH . "assets/plugins/CommerceDiscounts/controllers/" . $controllerName . ".php")) {
        $controllerName = 'CommerceDiscountsController';
    }
    include_once MODX_BASE_PATH . "assets/plugins/CommerceDiscounts/controllers/" . $controllerName . ".php";
    $controllerName = "\\CommerceDiscounts\\" . $controllerName;
    $controller = $controllerName::getInstance($params);
}

switch ($e->name) {

    case 'OnWebPageInit':
        $controller->regScripts();
        $controller->setActiveDiscountsForUser();
        break;
        
    case 'OnPageNotFound':
        $controller->regScripts();
        $controller->setActiveDiscountsForUser();

        //пересчет корзины при изменениях так, чтобы учитывались все скидки
        if ($_REQUEST['q'] == 'cart/update/discount') {
            $cart = ci()->carts->getCart('products');
            switch ($_REQUEST['action']) {
                case 'recount':
                    $row = $modx->db->escape($_REQUEST['row']);
                    $newCount = $modx->db->escape($_REQUEST['count']);
                    $responce = $controller->recountCartItems($cart, $row, $newCount);
                    $controller->getDiscount('productCart', ['cartItems' => ci()->carts->getCart('products')->getItems()]);
                    break;

                case 'remove':
                    $row = $modx->db->escape($_REQUEST['row']);
                    $responce = $controller->removeCartRow($cart, $row);
                    $controller->getDiscount('productCart', ['cartItems' => ci()->carts->getCart('products')->getItems()]);
                    break;

                case 'update': 
                    $controller->getDiscount('productCart', ['cartItems' => ci()->carts->getCart('products')->getItems()]);
                    $responce = ['status' => 'reload'];
                    break;

                default:
                    $responce = ['status' => 'silent'];
                    break;
            }
            echo json_encode($responce);
            exit();
        }
        break;
        
    case 'OnCollectSubtotals':
        //скидки, применяемые ко всей сумме корзины
        $discount = $controller->getDiscount('cart', $params);
        if (!empty($discount) && !empty($discount['discountSumm'])) {
            $params['total'] -= $discount['discountSumm'];
            $params['rows']['CommerceDiscounts'] = [
                'title' => 'Скидка для корзины (-' . $discount['discountFormatSumm'] . ')',
                'price' => -$discount['discountSumm'],
            ];
        }
        break;
    
    case 'OnBeforeCartItemAdding':
        //скидки, применяемые к конкретному товару при его добавлении
        $discount = $controller->getDiscount('product', $params);
        $originalPrice = $newPrice = !empty($params['item']['meta']['CommerceDiscounts']['originalPrice']) ? $params['item']['meta']['CommerceDiscounts']['originalPrice'] : $params['item']['price'];
        if (!empty($discount) && !empty($discount['discountSumm'])) {
            $params['item']['meta']['CommerceDiscounts'] = $controller->getMeta([
                'summ' => $discount['discountSumm'],
                'amount' => $discount['discountRow']['value'],
                'formatSumm' => $discount['discountFormatSumm'],
                'unit' => $discount['discountRow']['text'],
                'name' => $discount['discountRow']['name'],
                'id' => $discount['discountRow']['id'],
                'originalPrice' => $params['item']['price'],
            ]);
            $newPrice -= $discount['discountSumm'];
        }
        if (!empty($_SESSION['implementDiscount']) && ($originalPrice - $_SESSION['implementDiscount']['discountSumm']) < $newPrice) {
            $discount = $_SESSION['implementDiscount'];
            $params['item']['meta']['CommerceDiscounts'] = $controller->getMeta([
                'summ' => $discount['discountSumm'],
                'amount' => $discount['discountRow']['value'],
                'formatSumm' => $discount['discountFormatSumm'],
                'unit' => $discount['discountRow']['text'],
                'name' => $discount['discountRow']['name'],
                'id' => $discount['discountRow']['id'],
                'originalPrice' => $params['item']['price'],
            ]);
            $newPrice = $originalPrice - $_SESSION['implementDiscount']['discountSumm'];
        }
        $params['item']['price'] = $newPrice;
        break;
    
    case 'OnBeforeCartItemUpdating':
        break;

    case 'OnOrderSaved':
        $controller->destroyDiscounts();
        break;
    
    default:
        break;
}