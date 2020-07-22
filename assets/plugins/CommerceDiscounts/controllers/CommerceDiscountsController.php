<?php namespace CommerceDiscounts;

class CommerceDiscountsController
{
    private static $instance = null;
    
    protected $storeKey = 'CommerceDiscounts';
    
    public $precision = 2;
    
    public $currency = 'руб.';

    public function __construct($params = [])
    {
        $this->modx = EvolutionCMS();
        $this->params = $params;
        $this->table = $this->modx->getFullTableName("commerce_discounts");
    }

    public static function getInstance($params = [])
    {
        if (self::$instance === null) {
            self::$instance = new static($params);
        }
        return self::$instance;
    }

    public function regScripts()
    {
        $this->modx->regClientScript(MODX_SITE_URL . "assets/plugins/CommerceDiscounts/js/CommerceDiscounts.js");
    }

    public function setActiveDiscountsForUser() {
        $groups = $this->getWebuserGroups();
        $dicsounts = $this->getActiveDiscountsForUser($groups);
        $this->storeDiscounts($dicsounts);
        return $dicsounts;
    }
    
    public function getDiscount($name = 'cart', $params = [])
    {
        $discount = 0;
        if (!empty($this->getActiveDiscounts())) {
            $methodName = 'getDiscount' . strtoupper($name);
            if (is_callable([$this, $methodName])) {
                $discount = call_user_func([$this, $methodName], $params);
            }
        }
        return $discount;
    }
    
    public function destroyDiscounts()
    {
        if (isset($_SESSION[$this->storeKey])) {
            unset($_SESSION[$this->storeKey]);
        }
    }
    
    protected function getTable($table) {
        return $this->modx->getFullTableName($table);
    }
    
    protected function getCart($name = 'cart') {
        return !empty($_SESSION[$name]) ? $_SESSION[$name] : [];
    }
    
    protected function getWebuserGroups() {
        $groups = [];
        $groups[] = -1;//for all
        $uid = $this->modx->getLoginUserID("web");
        if (!empty($uid)) {
            $q = $this->modx->db->select("webgroup", $this->getTable("web_groups"), "webuser=" . $uid);
            while ($row = $this->modx->db->getRow($q)) {
                $groups[] = $row['webgroup'];
            }
        }
        return $groups;
    }
    
    protected function storeDiscounts($dicsounts)
    {
        $_SESSION[$this->storeKey] = $dicsounts;
        return;
    }
    
    protected function getActiveDiscountsForUser($groups = []) {
        $discounts = [];
        $q = $this->modx->db->select("id", $this->table, "`user_group` IN (" . implode(',', $groups) . ") AND `active`=1 AND (`date_start` IS NULL OR `date_start`<=CURDATE()) AND (`date_finish` IS NULL OR `date_finish`>=CURDATE())", "menuindex DESC");
        while ($row = $this->modx->db->getRow($q)) {
            $discounts[] = $row['id'];
        }
        return $discounts;
    }
    
    protected function getActiveDiscounts()
    {
        return !empty($_SESSION[$this->storeKey]) ? $_SESSION[$this->storeKey] : [];
    }
    
    protected function getDiscountCart($params = [])
    {
        $activeDiscounts = [];
        if (!empty($this->getActiveDiscounts())) {
            $q = $this->modx->db->select("*", $this->table, "id IN (" . implode(',', $this->getActiveDiscounts()) . ") AND discount_type=4", "menuindex DESC");
            while ($row = $this->modx->db->getRow($q)) {
                $activeDiscounts[] = $row;
            }
        }
        if (!empty($activeDiscounts)) {
            $discounts = [];
            $cartName = !empty($params['instance']) ? $params['instance'] : 'cart';
            $cart = $this->getCart($cartName);
            foreach ($activeDiscounts as $activeDiscount) {
                if ($this->validateDiscount('cart', $params, $activeDiscount, $cart)) {
                    $discounts[] = $this->makeDiscountAmount('cart', $params, $activeDiscount, $cart);
                }
            }
            
            return $this->getFitDiscount($discounts);
        }
    }
    
    protected function getDiscountProduct($params = [])
    {
        $activeDiscounts = [];
        if (!empty($this->getActiveDiscounts())) {
            $q = $this->modx->db->select("*", $this->table, "id IN (" . implode(',', $this->getActiveDiscounts()) . ") AND (discount_type IN (1,2) AND `info` NOT LIKE '%\"type\":\"3\"%')", "menuindex DESC");
            while ($row = $this->modx->db->getRow($q)) {
                $activeDiscounts[] = $row;
            }
        }
        if (!empty($activeDiscounts)) {
            $discounts = [];
            $cartName = !empty($params['instance']) ? $params['instance'] : 'cart';
            $cart = $this->getCart($cartName);
            foreach ($activeDiscounts as $activeDiscount) {
                if ($this->validateDiscount('product', $params, $activeDiscount, $cart)) {
                    $discounts[] = $this->makeDiscountAmount('product', $params, $activeDiscount, $cart);
                }
            }
            
            return $this->getFitDiscount($discounts);
        }
    }

    protected function getDiscountProductCart($params = [])
    {
        $activeDiscounts = [];
        if (!empty($this->getActiveDiscounts())) {
            $q = $this->modx->db->select("*", $this->table, "id IN (" . implode(',', $this->getActiveDiscounts()) . ") AND (discount_type IN (1,2) AND `info` LIKE '%\"type\":\"3\"%')", "menuindex DESC");
            while ($row = $this->modx->db->getRow($q)) {
                $activeDiscounts[] = $row;
            }
        }
        if (!empty($activeDiscounts)) {
            $cart = $params['cartItems'];
            $cartItemsIds = [];
            foreach ($cart as $item) {
                if (!in_array($item['id'], $cartItemsIds)) {
                    $cartItemsIds[] = $item['id'];
                }
            }
            if (!empty($cartItemsIds)) {
                foreach ($cartItemsIds as $cartItemsId) {
                    $discounts = [];
                    foreach ($activeDiscounts as $activeDiscount) {
                        if ($this->validateDiscount('productCart', ['item' => ['id' => $cartItemsId]], $activeDiscount, $cart)) {
                            $discounts[] = $this->makeDiscountAmount('productCart', ['item' => ['id' => $cartItemsId]], $activeDiscount, $cart);
                        }
                    }
                    if (!empty($discounts)) {
                        $fitDiscount = $this->getFitDiscount($discounts);
                        $this->implementDiscountProductCart($cartItemsId, $fitDiscount, $cart);
                    }
                }
            }
            //return $this->getFitDiscount($discounts);
        }
    }
    
    protected function implementDiscountProductCart($cartItemsId, $fitDiscount, $cart)
    {
        $newItems = $this->restoreItems($cart, $cartItemsId);
        $_SESSION['implementDiscount'] = [ 'discountSumm' => $fitDiscount['discountSumm'], 'discountRow' => $fitDiscount['discountRow'], 'discountFormatSumm' => $fitDiscount['discountFormatSumm'] ];
        ci()->carts->getCart('products')->removeById($cartItemsId);
        foreach ($newItems as $newItem) {
            ci()->carts->getCart('products')->add( $newItem );
        }
        unset($_SESSION['implementDiscount']);
    }
    
    protected function getFitDiscount($discounts)
    {
        $discountSumm = 0;
        $discountFormatSumm = 0;
        $discountRow = [];
        if (!empty($discounts)) {
            $sums = array_column($discounts, 'discountSumm');
            $discountSumm = max($sums);
            $key = array_search($discountSumm, $sums);
            $discountRow = $discounts[$key]['discountRow'];
            $discountFormatSumm = $discountRow['text'] == '%' ? $discountRow['value'] . $discountRow['text'] : ci()->currency->format($discountSumm);
        }
        return ['discountSumm' => $discountSumm, 'discountFormatSumm' => $discountFormatSumm, 'discountRow' => $discountRow];
    }
    
    protected function validateDiscount($name = 'cart', $params = [], $discountRow = [], $cart = [])
    {
        $check = false;
        $check = $this->checkConditions($name, $params, $discountRow, $cart);
        if ($check) {
            $check = $this->checkRules($name, $params, $discountRow, $cart);
        }
        return $check;
    }
    
    protected function checkConditions($name = 'cart', $params = [], $discountRow = [], $cart = [])
    {
        $check = false;
        $methodName = 'checkConditions' . strtoupper($name);
        if (is_callable([$this, $methodName])) {
            $check = call_user_func([$this, $methodName], $params, $discountRow, $cart);
        }
        return $check;
    }
    
    protected function checkRules($name = 'cart', $params = [], $discountRow = [], $cart = [])
    {
        $check = false;
        $methodName = 'checkRules' . strtoupper($name);
        if (is_callable([$this, $methodName])) {
            $check = call_user_func([$this, $methodName], $params, $discountRow, $cart);
        }
        return $check;
    }
    
    protected function makeDiscountAmount($name = 'cart', $params = [], $discountRow = [], $cart = [])
    {
        $summ = 0;
        $methodName = 'makeDiscountAmount' . strtoupper($name);
        if (is_callable([$this, $methodName])) {
            $summ = call_user_func([$this, $methodName], $params, $discountRow, $cart);
        }
        return $summ;
    }
    
    protected function checkConditionsCart($params, $discountRow, $cart)
    {
        $check = false;
        $info = !empty($discountRow['info']) ? json_decode($discountRow['info'], true) : [];
        if (!empty($info['conditions'])) {
            $conditions = $info['conditions'];
            switch (true) {
                case ($conditions['item'] == 2 && $conditions['type'] == 2 && $params['total'] >= $this->convertFromDefault($conditions['count'])):
                    //начиная с определенной суммы в корзине
                    $check = true;
                    break;
                case ($conditions['item'] == 1 && $conditions['type'] == 2):
                    //начиная с определенного количества товаров в корзине
                    $cnt = 0;
                    foreach ($cart as $item) {
                        $cnt += $item['count'];
                    }
                    $check = $cnt >= $conditions['count'];
                    break;
                default:
                    break;
            }
        }
        return $check;
    }
    
    protected function checkConditionsProduct($params, $discountRow, $cart)
    {
        $check = false;
        $check = $this->checkElementsProduct($params, $discountRow, $cart);
        if (!$check) return $check;
        
        $info = !empty($discountRow['info']) ? json_decode($discountRow['info'], true) : [];
        if (!empty($info['conditions'])) {
            $conditions = $info['conditions'];
            $productCartStat = $this->getProductCartStat($cart, $params['item']['id']);
            switch (true) {
                case ($conditions['item'] == 2 && $conditions['type'] == 2):
                    //начиная с определенной суммы за данный товар
                    $summ = $params['item']['count'] * $params['item']['price'] + $productCartStat['summ'];
                    $check = $summ >= $this->convertFromDefault($conditions['count']);
                    break;
                case ($conditions['item'] == 1 && $conditions['type'] == 2):
                    //начиная с определенного количества данного товара в корзине
                    $count = $params['item']['count'] + $productCartStat['count'];
                    $check = $count >= $conditions['count'];
                    break;
                case ($conditions['item'] == 1 && $conditions['type'] == 1):
                    //для каждого n-го товара в корзине
                    $count = $params['item']['count'] + $productCartStat['count'];
                    $check = ($count % $conditions['count'] === 0);
                    break;
                default:
                    $check = false;
                    break;
            }
        }
        return $check;
    }

    protected function checkConditionsProductCart($params, $discountRow, $cart)
    {
        $check = false;
        $id = $params['item']['id'];
        $check = $this->checkElementsProduct($params, $discountRow, $cart);
        if (!$check) return $check;
        
        $check = false;
        $newItems = $this->restoreItems($cart, $id);
        $productCartStat = $this->getProductCartStat($cart, $id);
        $conditions = json_decode($discountRow['info'], true)['conditions'];
        
        switch (true) {
            case ($conditions['item'] == 1 && $productCartStat['count'] >= $conditions['count']):
                //при достижении определенного количества штук
                $check = true;
                break;
            case ($conditions['item'] == 2 && $productCartStat['summ'] >= $this->convertFromDefault($conditions['count'])):
                //при достижении определенной суммы
                $check = true;
                break;
            default:
                break;
        }
        return $check;
    }
    
    protected function checkElementsProduct($params, $discountRow, $cart)
    {
        $check = false;
        $elements = !empty($discountRow['elements']) ? json_decode($discountRow['elements'], true) : [];
        switch (true) {
            case ($discountRow['discount_type'] == 1):
                //скидка для категорий, проверяем, что товар принадлежит категории
                $check = $this->checkParents($params['item']['id'], $elements);
                if (!$check) {
                    $check = $this->checkTvParents($params['item']['id'], $elements);
                }
                if (!$check) {
                    $check = $this->checkMulticategoriesParents($params['item']['id'], $elements);
                }
                break;
            case ($discountRow['discount_type'] == 2):
                //скидка для списка товаров, проверяем, что товар входит в список
                $check = in_array($params['item']['id'], $elements);
                break;
            default:
                $check = false;
                break;
        }
        return $check;
    }
    
    protected function checkRulesCart($params, $discountRow, $cart)
    {
        return true;
    }
    
    protected function checkRulesProduct($params, $discountRow, $cart)
    {
        return true;
    }
    
    protected function checkRulesProductCart($params, $discountRow, $cart)
    {
        return true;
    }
    
    protected function checkParents($docid, $parents = [], $withDoc = false)
    {
        if (empty($parents)) return true;//если не задана ни одна категория, считаем, что скидка для всего каталога
        $parentIds = $this->modx->getParentIds($docid);
        if ($withDoc) {
            $parentIds[] = $docid;
        }
        return count(array_intersect($parents, $parentIds)) > 0;
    }
    
    protected function checkTvParents($docid, $parents = [])
    {
        $check = false;
        $TvParents = [];
        if (!empty($this->params['parents_tvnames'])) {
            $tvs = $this->modx->getTemplateVarOutput(explode(',', $this->params['parents_tvnames']), $docid, 1);
            foreach ($tvs as $k => $v) {
                $values = array_map('trim', explode(',', $v));
                foreach ($values as $value) {
                    if (is_numeric($value)) {
                        $TvParents[] = $value;
                    }
                }
            }
        }
        if (!empty($TvParents)) {
            foreach ($TvParents as $TvParent) {
                $check = $this->checkParents($TvParent, $parents, true);
                if ($check) break;
            }
        }
        return $check;
    }
    
    protected function checkMulticategoriesParents($docid, $parents = [])
    {
        return false;
    }
    
    protected function makeDiscountAmountCart($params, $discountRow, $cart)
    {
        $discount = 0;
        $cart_summ = $params['total'];
        if ($this->checkExcludeSale($discountRow)) {
            $cart_summ = $this->makeExcludeSale($cart);
        }
        if (!empty($discountRow['discount']) && (double)$discountRow['discount'] > 0) {
            //скидка в процентах в приоритете
            $discount = round($cart_summ * (double)$discountRow['discount'] / 100, $this->precision);
            $discountRow['value'] = (double)$discountRow['discount'];
            $discountRow['text'] = '%';
        } else if (!empty($discountRow['discount_summ']) && (double)$discountRow['discount_summ'] > 0) {
            //скидка в твердой сумме
            $discount = $this->convertFromDefault((double)$discountRow['discount_summ']);
            if ($discount > $cart_summ) {
                $discount = $cart_summ;
            }
            $discountRow['value'] = (double)$discount;
            $discountRow['text'] = $this->currency;
            $discount = round($discount, $this->precision);
        }
        return [ 'discountSumm' => $discount, 'discountRow' => $discountRow ];
    }
    
    protected function makeDiscountAmountProduct($params, $discountRow, $cart)
    {
        $discount = 0;
        if (!empty($discountRow['discount']) && (double)$discountRow['discount'] > 0) {
            //скидка в процентах в приоритете
            $discount = round($params['item']['price'] * (double)$discountRow['discount'] / 100, $this->precision);
            $discountRow['value'] = (double)$discountRow['discount'];
            $discountRow['text'] = '%';
        } else if (!empty($discountRow['discount_summ']) && (double)$discountRow['discount_summ'] > 0) {
            //скидка в твердой сумме
            $discount = $this->convertFromDefault((double)$discountRow['discount_summ']);
            if ($discount > $params['item']['price']) {
                $discount = $params['item']['price'];
            }
            $discountRow['value'] = (double)$discount;
            $discountRow['text'] = $this->currency;
            $discount = round($discount, $this->precision);
        }
        return [ 'discountSumm' => $discount, 'discountRow' => $discountRow ];
    }

    protected function makeDiscountAmountProductCart($params, $discountRow, $cart)
    {
        $discount = 0;
        $originalPrice = 0;
        foreach ($cart as $item) {
            if ($item['id'] == $params['item']['id']) {
                $originalPrice = !empty($item['meta']['CommerceDiscounts']['originalPrice']) ? $item['meta']['CommerceDiscounts']['originalPrice'] : $item['price'];
                break;
            }
        }
        if (!empty($discountRow['discount']) && (double)$discountRow['discount'] > 0) {
            //скидка в процентах в приоритете
            $discount = round($originalPrice * (double)$discountRow['discount'] / 100, $this->precision);
            $discountRow['value'] = (double)$discountRow['discount'];
            $discountRow['text'] = '%';
        } else if (!empty($discountRow['discount_summ']) && (double)$discountRow['discount_summ'] > 0) {
            //скидка в твердой сумме
            $discount = $this->convertFromDefault((double)$discountRow['discount_summ']);
            if ($discount > $originalPrice) {
                $discount = $originalPrice;
            }
            $discountRow['value'] = (double)$discount;
            $discountRow['text'] = $this->currency;
            $discount = round($discount, $this->precision);
        }
        return [ 'discountSumm' => $discount, 'discountRow' => $discountRow ];
    }
    
    protected function getProductCartStat($cart, $product_id)
    {
        $cartStat = ['count' => 0, 'summ' => 0];
        $cartRows = [];
        if (!empty($cart)) {
            foreach ($cart as $row) {
                if ($row['id'] == $product_id) {
                    $cartRows[] = $row;
                }
            }
        }
        foreach ($cartRows as $cartRow) {
            $cartStat['count'] += $cartRow['count'];
            $cartStat['summ'] += $cartRow['count'] * $cartRow['price'];
        }
        return $cartStat;
    }
    
    protected function checkExcludeSale($discountRow)
    {
        return !empty($discountRow['info']) && !empty(json_decode($discountRow['info'], true)['exclude_sales']);
    }

    protected function makeExcludeSale($cart)
    {
        $summ = 0;
        foreach ($cart as $row) {
            if (!isset($row['meta']['CommerceDiscounts'])) {
                $summ += $row['price'] * $row['count'];
            }
        }
        return $summ;
    }

    public function recountCartItems($cart, $row, $newCount)
    {
        $responce = ['status' => 'silent'];
        $items = $cart->getItems();
        if (!empty($items[$row])) {
            $oldCount = $items[$row]['count'];
            $diff = $newCount - $oldCount;
            if ($diff > 0) {
                //нужно просто добавить разницу в корзину, предварительно очистив
                $newItem = $items[$row];
                $newItem['count'] = 1;
                $newItem = $this->cleanDiscountInfo($newItem);
                for ($i = 0; $i < $diff; $i++) {
                    $cart->add( $newItem );
                }
                $responce = ['status' => 'recount'];
            } else if ($diff < 0) {
                
                //сначала отнять нужное количество из нужной строки, а остальные строки 
                //пересобрать по новой, удалив их предварительно
                $id = $items[$row]['id'];
                $items[$row]['count'] += $diff;

                //собираем все оставшиеся модификации данного товара
                $newItems = $this->restoreItems($items, $id);

                //удаляем все модификации данного товара из корзины
                $cart->removeById($id);

                //и добавляем их снова поштучно для просчета новых скидок
                foreach ($newItems as $newItem) {
                    $cart->add( $newItem );
                }
                $responce = ['status' => 'recount'];
            } else {}
        }
        return $responce;
    }
    
    public function removeCartRow($cart, $row)
    {
        $responce = ['status' => 'silent'];

        $items = $cart->getItems();
        $id = $items[$row]['id'];

        //удаляем строку с конкретной модификацией
        $cart->remove($row);

        //берем оставшиеся строки корзины
        $items = $cart->getItems();

        //собираем все оставшиеся модификации данного товара
        $newItems = $this->restoreItems($items, $id);

        //удаляем все модификации данного товара из корзины
        $cart->removeById($id);

        //и добавляем их снова поштучно для просчета новых скидок
        foreach ($newItems as $newItem) {
            $cart->add( $newItem );
        }
        $responce = ['status' => 'remove'];
        return $responce;
    }
    
    protected function restoreItems($items, $id)
    {
        $newItems = [];
        foreach ($items as $key => $item) {
            if ($item['id'] == $id) {
                $item = $this->cleanDiscountInfo($item);
                $loop = $item['count'];
                $item['count'] = 1;
                for ($i = 0; $i < $loop; $i++) {
                    $newItems[] = $item;
                }
            }
        }
        return $newItems;
    }
    
    protected function cleanDiscountInfo($item = [])
    {
        if (!empty($item['meta']['CommerceDiscounts']['originalPrice'])) {
            $item['price'] = $item['meta']['CommerceDiscounts']['originalPrice'];
            unset($item['meta']['CommerceDiscounts']);
        }
        unset($item['hash'], $item['row']);
        return $item;
    }

    protected function convertFromDefault($amount)
    {
        if (function_exists('ci')) {
            $amount = ci()->currency->convertFromDefault($amount);
        }
        return $amount;
    }
    
    public function getMeta($meta = [])
    {
        return $meta;
    }
    
    public function getHashes($withCount = false, $sort = false)
    {
        $hashes = [];
        $cart = ci()->carts->getCart('products')->getItems();
        $hashes = [];
        foreach ($cart as $item) {
            $hashes[] = $item['hash'] . ($withCount ? $item['count'] : '');
        }
        if ($sort) {
            sort($hashes);
        }
        return $hashes;
    }

}
