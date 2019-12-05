//<?
/**
 * Скидки
 * 
 * Управление системой скидок
 * 
 * @author      webber (web-ber12@yandex.ru)
 * @category    module
 * @version     0.1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @guid webixtable
 * @internal    @properties &name=Заголовок модуля;text;Скидки;;Отображается над таблицей&table=Имя таблицы в БД;text;commerce_discounts;&idField=Уник.поле в БД;text;id&fields=Имена полей в таблице БД;text;id, name, discount_type, user_group, date_create, date_start, date_finish, discount, discount_summ, active;;Через запятую&fields_names=Названия колонок в таблице модуля;text;ID, Название, Тип, Группа, Создан, Начало, Конец, Скидка%, Скидка руб, Активный;;Через запятую&fields_readonly=Поля, для которых запрещено редактирование;text;id,date_create;;Поля БД через запятую&display=Показывать по;text;20&tpl=Имя шаблона (без .tpl);text;discounts;&controller_name=Имя контроллера;text;discounts;&catalog_roots=Корневые папки каталога;text;;0;&products_templates=ID шаблонов товара;text;;1;
 * @internal    @modx_category Commerce
 * @internal    @installset base, sample
 */
 
include_once MODX_BASE_PATH . "assets/modules/webixtable/module.webixtable.php";
