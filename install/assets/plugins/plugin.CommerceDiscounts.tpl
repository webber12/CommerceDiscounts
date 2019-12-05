//<?php
/**
 * CommerceDiscounts
 *
 * Плагин для управления скидками магазина Commerce
 *
 * @author      webber (web-ber12@yandex.ru)
 * @category    plugin
 * @version     0.1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @events OnWebPageInit,OnCollectSubtotals,OnOrderSaved,OnBeforeCartItemAdding,OnBeforeCartItemUpdating,OnPageNotFound
 * @internal    @properties &controller_name=Имя контроллера;string;CommerceDiscountsController;CommerceDiscountsController&parents_tvnames=Имена TV с родителями;string;category,brand;
 * @internal    @installset base, sample
 * @internal    @modx_category Commerce
 */
 
require MODX_BASE_PATH.'assets/plugins/CommerceDiscounts/plugin.CommerceDiscounts.php';