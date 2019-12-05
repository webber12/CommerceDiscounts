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

        //пересчет корзины так, чтобы учитывались все скидки
        if ($_REQUEST['q'] == 'cart/update/discount') {
            $cart = ci()->carts->getCart('products');
            switch ($_REQUEST['action']) {
                case 'recount':
                    $row = $modx->db->escape($_REQUEST['row']);
                    $newCount = $modx->db->escape($_REQUEST['count']);
                    $responce = $controller->recountCartItems($cart, $row, $newCount);
                    break;
                case 'remove':
                    $row = $modx->db->escape($_REQUEST['row']);
                    $responce = $controller->removeCartRow($cart, $row);
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
        $discount = $controller->getDiscount('cart', $params);
        if (!empty($discount) && !empty($discount['discountSumm'])) {
            $params['total'] -= $discount['discountSumm'];
            $params['rows']['CommerceDiscounts'] = [
                'title' => 'Скидка для корзины (-' . $discount['discountRow']['value'] . ' ' . $discount['discountRow']['text'] . ')',
                'price' => -$discount['discountSumm'],
            ];
        }
        break;
    
    case 'OnBeforeCartItemAdding':
        $discount = $controller->getDiscount('product', $params);
        if (!empty($discount) && !empty($discount['discountSumm'])) {
            $params['item']['meta']['CommerceDiscounts']['summ'] = $discount['discountSumm'];
            $params['item']['meta']['CommerceDiscounts']['amount'] = $discount['discountRow']['value'];
            $params['item']['meta']['CommerceDiscounts']['unit'] = $discount['discountRow']['text'];
            $params['item']['meta']['CommerceDiscounts']['name'] = $discount['discountRow']['name'];
            $params['item']['meta']['CommerceDiscounts']['id'] = $discount['discountRow']['id'];
            $params['item']['meta']['CommerceDiscounts']['originalPrice'] = $params['item']['price'];
            $params['item']['price'] -= $discount['discountSumm'];
        }
        break;
    
    case 'OnBeforeCartItemUpdating':
        //todo
        /*if (!empty($_POST['action']) && $_POST['action'] == 'cart/update') {
            $cart = ci()->carts->getCart('products');
            $items = $cart->getItems();
            //$params['attributes']['price'] = 120;
        }*/
        break;
        
    case 'OnOrderSaved':
        $controller->destroyDiscounts();
        break;
    
    default:
        break;
}