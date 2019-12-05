<?php namespace WebixTable;

include_once ("main.controller.php");

class DiscountsController extends \WebixTable\MainController
{

    protected $inline_fields_width_default = 100;

    protected $inline_fields_width = ['id' => 80, 'name' => 240, 'date_start' => 85, 'date_finish' => 85, 'date_create' => 85, 'discount_summ' => 120];
    
    protected $checkbox_fields = ['active'];

    protected $cfg_defaults = ['idField' => 'id', 'display' => '20', 'tpl' => 'discounts', 'controller_name' => 'discounts', 'inline_edit' => 0, 'modal_edit' => 1];

    public function plural($number, $after) {
        //array('комментарий','комментария','комментариев')
        $cases = array (2, 0, 1, 1, 1, 2);
        return $after[ ($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)] ];
    }
    
    protected function WebuserGroupsList()
    {
        $groups = [];
        $groups[] = ['id' => -1, 'value' => 'Для всех'];
        $q = $this->modx->db->query("SELECT * FROM " . $this->modx->getFullTableName("webgroup_names") . " ORDER BY name ASC");
        while ($row = $this->modx->db->getRow($q)) {
            $groups[] = ['id' => $row['id'], 'value' => $row['name']];
        }
        return $groups;
    }

    protected function getDiscount($id)
    {
        $arr = [];
        $q = $this->modx->db->query("SELECT * FROM " . $this->getTable() . " WHERE id=" . $id . " LIMIT 0,1");
        if ($this->modx->db->getRecordCount($q) == 1) {
            $arr = $this->modx->db->getRow($q);
        }
        return $arr;
    }

    protected function invokeOnAfterRenderColumns($data)
    {
        $tmp = $data;
        foreach ($data as $k => $v) {
            if (in_array($v['id'], $this->checkbox_fields)) {
                $tmp[$k]['editor'] = "checkbox";
                $tmp[$k]['template'] = "{common.checkbox()}";
            }
            if ($v['id'] == 'discount_type') {
                $tmp[$k]['editor'] = "select";
                $tmp[$k]['options'] = [ ['id' => 1, 'value' => 'Категории'], ['id' => 2, 'value' => 'Товары'], ['id' => 4, 'value' => 'Вся корзина'] ];
            }
            if ($v['id'] == 'user_group') {
                $tmp[$k]['editor'] = "select";
                $tmp[$k]['options'] = $this->WebuserGroupsList();
            }
        }
        $data = $tmp;
        return $data;
    }

    protected function invokeOnGetCfg($data)
    {
        if (key($data) == 'inline_edit') {
            $data['inline_edit'] = 0;
        }
        if (key($data) == 'fields_modalform') {
            $data['fields_modalform'] = $this->getCfg('fields');
        }
        return $data;
    }

    protected function invokeOnBeforeUpdateInline($data)
    {
        if (empty($data['date_start'])) $data['date_start'] = NULL;
        if (empty($data['date_finish'])) $data['date_finish'] = NULL;
        return $data;
    }

    protected function invokeOnBeforeRenderModalData($data)
    {
        //данные для обновления строки таблицы
        return $data;
    }

    protected function invokeOnBeforeSaveCategoryForm($data = [])
    {
        $up = [];
        $up['name'] = !empty($_REQUEST['name']) ? $_REQUEST['name'] : "";
        $up['date_start'] = !empty($_REQUEST['date_start']) ? explode(" ", $_REQUEST['date_start'])[0] : NULL;
        $up['date_finish'] = !empty($_REQUEST['date_finish']) ? explode(" ", $_REQUEST['date_finish'])[0] : NULL;
        $up['discount'] = !empty($_REQUEST['discount']) ? $_REQUEST['discount'] : 0;
        $up['discount_summ'] = !empty($_REQUEST['discount_summ']) && empty($_REQUEST['discount']) ? $_REQUEST['discount_summ'] : 0;
        $up['active'] = !empty($_REQUEST['active']) ? 1 : 0;
        $up['user_group'] = !empty($_REQUEST['user_group']) ? $_REQUEST['user_group'] : -1;
        $info = [];
        $info['type'] = !empty($_REQUEST['condition_type']) ? $_REQUEST['condition_type'] : 1;
        $info['count'] = !empty($_REQUEST['condition_count']) ? $_REQUEST['condition_count'] : 1;
        $info['item'] = !empty($_REQUEST['condition_item']) ? $_REQUEST['condition_item'] : 1;
        $up['info'] = json_encode(['conditions' => $info]);
        $up = $this->modx->db->escape($up);
        if (empty($up['date_start'])) $up['date_start'] = NULL;
        if (empty($up['date_finish'])) $up['date_finish'] = NULL;
        return $up;
    }

    protected function invokeOnBeforeSaveProductForm($data = [])
    {
        $up = [];
        $up['name'] = !empty($_REQUEST['name']) ? $_REQUEST['name'] : "";
        $up['date_start'] = !empty($_REQUEST['date_start']) ? explode(" ", $_REQUEST['date_start'])[0] : NULL;
        $up['date_finish'] = !empty($_REQUEST['date_finish']) ? explode(" ", $_REQUEST['date_finish'])[0] : NULL;
        $up['discount'] = !empty($_REQUEST['discount']) ? $_REQUEST['discount'] : 0;
        $up['discount_summ'] = !empty($_REQUEST['discount_summ']) && empty($_REQUEST['discount']) ? $_REQUEST['discount_summ'] : 0;
        $up['active'] = !empty($_REQUEST['active']) ? 1 : 0;
        $up['user_group'] = !empty($_REQUEST['user_group']) ? $_REQUEST['user_group'] : -1;
        $info = [];
        $info['type'] = !empty($_REQUEST['condition_type']) ? $_REQUEST['condition_type'] : 1;
        $info['count'] = !empty($_REQUEST['condition_count']) ? $_REQUEST['condition_count'] : 1;
        $info['item'] = !empty($_REQUEST['condition_item']) ? $_REQUEST['condition_item'] : 1;
        $up['info'] = json_encode(['conditions' => $info]);
        $up = $this->modx->db->escape($up);
        if (empty($up['date_start'])) $up['date_start'] = NULL;
        if (empty($up['date_finish'])) $up['date_finish'] = NULL;
        return $up;
    }

    protected function invokeOnBeforeLoadCategoryForm($data)
    {
        $info = !empty($data['info']) ? json_decode($data['info'], true) : [];
        $data['discount'] = (double)$data['discount'];
        $data['discount_summ'] = (double)$data['discount_summ'];
        $data['condition_type'] = !empty($info['conditions']['type']) ? $info['conditions']['type'] : 1;
        $data['condition_count'] = !empty($info['conditions']['count']) ? $info['conditions']['count'] : 1;
        $data['condition_item'] = !empty($info['conditions']['item']) ? $info['conditions']['item'] : 1;
        unset($data['info'], $data['elements'], $data['date_create']);
        return $data;
    }

    protected function invokeOnBeforeLoadProductForm($data)
    {
        $info = !empty($data['info']) ? json_decode($data['info'], true) : [];
        $data['discount'] = (double)$data['discount'];
        $data['discount_summ'] = (double)$data['discount_summ'];
        $data['condition_type'] = !empty($info['conditions']['type']) ? $info['conditions']['type'] : 1;
        $data['condition_count'] = !empty($info['conditions']['count']) ? $info['conditions']['count'] : 1;
        $data['condition_item'] = !empty($info['conditions']['item']) ? $info['conditions']['item'] : 1;
        unset($data['info'], $data['elements'], $data['date_create']);
        return $data;
    }

    protected function invokeOnBeforeSaveCartForm($data = [])
    {
        $up = [];
        $up['name'] = !empty($_REQUEST['name']) ? $_REQUEST['name'] : "";
        $up['date_start'] = !empty($_REQUEST['date_start']) ? explode(" ", $_REQUEST['date_start'])[0] : NULL;
        $up['date_finish'] = !empty($_REQUEST['date_finish']) ? explode(" ", $_REQUEST['date_finish'])[0] : NULL;
        $up['discount'] = !empty($_REQUEST['discount']) ? $_REQUEST['discount'] : 0;
        $up['discount_summ'] = !empty($_REQUEST['discount_summ']) && empty($_REQUEST['discount']) ? $_REQUEST['discount_summ'] : 0;
        $up['active'] = !empty($_REQUEST['active']) ? 1 : 0;
        $up['user_group'] = !empty($_REQUEST['user_group']) ? $_REQUEST['user_group'] : -1;
        $info = [];
        $info['type'] = !empty($_REQUEST['condition_type']) ? $_REQUEST['condition_type'] : 1;
        $info['count'] = !empty($_REQUEST['condition_count']) ? $_REQUEST['condition_count'] : 0;
        $info['item'] = !empty($_REQUEST['condition_item']) ? $_REQUEST['condition_item'] : 1;
        $up['info'] = json_encode(['conditions' => $info]);
        $info['exclude_sales'] = !empty($_REQUEST['exclude_sales']) ? 1 : 0;
        $up['elements'] = json_encode(['exclude_sales' => $info['exclude_sales']]);
        $up = $this->modx->db->escape($up);
        if (empty($up['date_start'])) $up['date_start'] = NULL;
        if (empty($up['date_finish'])) $up['date_finish'] = NULL;
        return $up;
    }
    
    protected function invokeOnBeforeLoadCartForm($data)
    {
        $info = !empty($data['info']) ? json_decode($data['info'], true) : [];
        $elements = !empty($data['elements']) ? json_decode($data['elements'], true) : [];
        $data['discount'] = (double)$data['discount'];
        $data['discount_summ'] = (double)$data['discount_summ'];
        $data['condition_type'] = !empty($info['conditions']['type']) ? $info['conditions']['type'] : 1;
        $data['condition_count'] = !empty($info['conditions']['count']) ? $info['conditions']['count'] : 0;
        $data['condition_item'] = !empty($info['conditions']['item']) ? $info['conditions']['item'] : 1;
        $data['exclude_sales'] = isset($elements['exclude_sales']) ? $elements['exclude_sales'] : 1;
        unset($data['info'], $data['elements'], $data['date_create']);
        return $data;
    }

    public function ajaxUpdate()
    {
        $arr = array();
        $idField = $this->getCfg('idField');
        foreach ($this->getCfg('fields') as $field) {
            if (isset($_REQUEST[$field])) {
                $arr[$field] = $this->modx->db->escape($_REQUEST[$field]);
            }
        }
        $opetarion = isset($_REQUEST['webix_operation']) ? $_REQUEST['webix_operation'] : '';
        switch ($opetarion) {
            case 'update':
                if (!empty($arr) && isset($arr[$idField]) && $arr[$idField] != '') {
                    foreach ($arr as $k => $v) {
                        if (preg_match('/^href_/', $k)) {//удаляем преобразованные в ссылки адреса
                            unset($arr[$k]);
                        }
                    }
                    $arr = $this->prepare($arr, 'OnBeforeUpdateInline');
                    $this->modx->db->update($arr, $this->getTable(), "`" . $idField . "`='" . $arr[$idField] . "'");
                    $arr = $this->prepare($arr, 'OAfterUpdateInline');
                }
                break;
            case 'insert':
                if (!empty($arr)) {
                    $this->modx->db->insert($arr, $this->getTable());
                }
                break;
            case 'delete':
                if (!empty($arr) && isset($arr[$idField]) && $arr[$idField] != '') {
                    $this->modx->db->delete($this->getTable(), "`" . $idField . "`='" . $arr[$idField] . "'");
                }
                break;
        }
    }

    public function ajaxLoadCategoryTree()
    {
        $elements = [];
        if (!empty($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
            $discount_id = (int)$_REQUEST['id'];
            $discount = $this->getDiscount($discount_id);
            if (!empty($discount['elements'])) {
                $elements = json_decode($discount['elements'], true);
            }
        }
        $DLMenuParams = [
            'parents' => $this->getCfg('catalog_roots'),
            'maxDepth' => 4,
            'api' => 1,
            'showParent' => 1,
            'prepare' => function($data, $modx, $_DL, $_eDL) use ($elements) {
                $data['value'] = $data['pagetitle'];
                $data['data'] = $data['children'];
                $tmp = $data;
                foreach ($data as $k => $v) {
                    if (!in_array($k, ['id', 'value', 'data', 'parent'])) {
                        unset($tmp[$k]);
                    }
                }
                if (is_array($elements) && in_array($tmp['id'], $elements)) {
                    $tmp['checked'] = true;
                }
                $data = $tmp;
                return $data;
            }
        ];
        if (!empty($this->getCfg('products_templates'))) {
            $DLMenuParams['addWhereList'] = 'c.template NOT IN (' . $this->getCfg('products_templates') . ')';
        }
        $json = $this->modx->runSnippet("DLMenu", $DLMenuParams);
        return json_encode(json_decode($json, true)[0]);
    }
    
    public function ajaxLoadCategoryList()
    {
        $output = [];
        if (!empty($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
            $discount_id = (int)$_REQUEST['id'];
            $discount = $this->getDiscount($discount_id);
            if (!empty($discount['elements'])) {
                $elements = json_decode($discount['elements'], true);
                $q = $this->modx->db->query("SELECT id, pagetitle FROM " . $this->modx->getFullTableName("site_content") . " WHERE id IN (" . implode(",", $elements) . ") ORDER BY pagetitle ASC");
                $count = $this->modx->db->getRecordCount($q);
                for ($i = 0; $i < $count; $i++) {
                    $row = $this->modx->db->getRow($q);
                    if ($i == 4 && $i < ($count - 1)) {
                        $output[] = ['id' => $row['id'], 'title' => $row['pagetitle'] . ' ... и еще ' . ($count - $i - 1) . ' ' . $this->plural(($count - $i - 1), ['категория', 'категории', 'категорий']) . ' ...'];
                        break;
                    } else {
                        $output[] = ['id' => $row['id'], 'title' => $row['pagetitle']];
                    }
                }
            }
        }
        return json_encode($output);
    }
    
    public function ajaxSaveCategoryTree()
    {
        $responce = 'error';
        if (!empty($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
            $id = (int)$_REQUEST['id'];
            $tree = [];
            if (!empty($_REQUEST['checked'])) {
                $tree = json_decode($_REQUEST['checked'], true);
                if ($this->modx->db->update(['elements' => json_encode($tree)], $this->getTable(), "id=" . $id)) {
                    $responce = 'ok';
                }
            }
        }
        return $responce;
    }

    public function ajaxLoadProductTree()
    {
        $elements = [];
        if (!empty($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
            $discount_id = (int)$_REQUEST['id'];
            $discount = $this->getDiscount($discount_id);
            if (!empty($discount['elements'])) {
                $elements = json_decode($discount['elements'], true);
            }
        }
        $DLparams = [
            'idType' => 'documents',
            'ignoreEmpty' => 1,
            'api' => '1',
            'makeUrl' => 0,
            'addWhereList' => 'c.template IN (' . $this->getCfg('products_templates') . ')',
            'orderBy' => 'pagetitle ASC, id DESC',
            'selectFields' => 'id,pagetitle',
            'prepare' => function($data, $modx, $_DL, $_eDL) use ($elements) {
                $data['value'] = $data['pagetitle'];
                return $data;
            }
        ];
        if (!empty($_REQUEST['selected'])) {
            if (!empty($elements)) {
                $DLparams['documents'] = implode(',', $elements);
            } else {
                $DLparams['documents'] = 4294967295;
            }
        } else {
            if (!empty($_REQUEST['checked'])) {
                $DLparams['addWhereList'] .= " AND c.id NOT IN (" . $this->modx->db->escape($_REQUEST['checked']) . ")";
            } else {
                if (!empty($elements)) {
                    $DLparams['addWhereList'] .= " AND c.id NOT IN (" . implode(',', $elements) . ")";
                }
            }
        }
        if (!empty($_REQUEST['search'])) {
            $DLparams['addWhereList'] .= " AND `c`.`pagetitle` LIKE '%" . $this->modx->db->escape($_REQUEST['search']) . "%'";
        }
        $json = $this->modx->runSnippet("DocLister", $DLparams);
        $result = json_decode($json, true);
        $arr = [];
        foreach ($result as $row) {
            $arr[] = $row;
        }
        return json_encode($arr);
    }

    public function ajaxLoadProductList()
    {
        $output = [];
        if (!empty($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
            $discount_id = (int)$_REQUEST['id'];
            $discount = $this->getDiscount($discount_id);
            if (!empty($discount['elements'])) {
                $elements = json_decode($discount['elements'], true);
                $q = $this->modx->db->query("SELECT id, pagetitle FROM " . $this->modx->getFullTableName("site_content") . " WHERE id IN (" . implode(",", $elements) . ") ORDER BY pagetitle ASC");
                $count = $this->modx->db->getRecordCount($q);
                for ($i = 0; $i < $count; $i++) {
                    $row = $this->modx->db->getRow($q);
                    if ($i == 4 && $i < ($count - 1)) {
                        $output[] = ['id' => $row['id'], 'title' => $row['pagetitle'] . ' ... и еще ' . ($count - $i - 1) . ' ' . $this->plural(($count - $i - 1), ['товар', 'товара', 'товаров']) . ' ...'];
                        break;
                    } else {
                        $output[] = ['id' => $row['id'], 'title' => $row['pagetitle']];
                    }
                }
            }
        }
        return json_encode($output);
    }

    public function ajaxSaveProductTree()
    {
        $responce = 'error';
        if (!empty($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
            $id = (int)$_REQUEST['id'];
            $tree = [];
            if (!empty($_REQUEST['checked'])) {
                $tree = json_decode($_REQUEST['checked'], true);
                if ($this->modx->db->update(['elements' => json_encode($tree)], $this->getTable(), "id=" . $id)) {
                    $responce = 'ok';
                }
            } else {
                if ($this->modx->db->update(['elements' => json_encode([])], $this->getTable(), "id=" . $id)) {
                    $responce = 'ok';
                }
            }
        }
        return $responce;
    }

    public function ajaxSaveCategoryForm()
    {
        $responce = 'error';
        if (!empty($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
            $id = (int)$_REQUEST['id'];
            $up = $this->prepare([], "OnBeforeSaveCategoryForm");
            if ($this->modx->db->update($up, $this->getTable(), "id=" . $id)) {
                $responce = 'ok';
            }
        }
        return $responce;
    }
    
    public function ajaxSaveProductForm()
    {
        $responce = 'error';
        if (!empty($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
            $id = (int)$_REQUEST['id'];
            $up = $this->prepare([], "OnBeforeSaveProductForm");
            if ($this->modx->db->update($up, $this->getTable(), "id=" . $id)) {
                $responce = 'ok';
            }
        }
        return $responce;
    }

    public function ajaxSaveCartForm()
    {
        $responce = 'error';
        if (!empty($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
            $id = (int)$_REQUEST['id'];
            $up = $this->prepare([], 'OnBeforeSaveCartForm');
            if ($this->modx->db->update($up, $this->getTable(), "id=" . $id)) {
                $responce = 'ok';
            }
        }
        return $responce;
    }

    public function ajaxLoadCategoryForm()
    {
        $responce = 'error';
        if (!empty($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
            $id = (int)$_REQUEST['id'];
            $discount = $this->getDiscount($id);
            if (!empty($discount)) {
                $discount = $this->prepare($discount, 'OnBeforeLoadCategoryForm');
                echo json_encode($discount);exit();
            }
        }
        return $responce;
    }
    
    public function ajaxLoadProductForm()
    {
        $responce = 'error';
        if (!empty($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
            $id = (int)$_REQUEST['id'];
            $discount = $this->getDiscount($id);
            if (!empty($discount)) {
                $discount = $this->prepare($discount, 'OnBeforeLoadProductForm');
                echo json_encode($discount);exit();
            }
        }
        return $responce;
    }

    public function ajaxLoadCartForm()
    {
        $responce = 'error';
        if (!empty($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) {
            $id = (int)$_REQUEST['id'];
            $discount = $this->getDiscount($id);
            if (!empty($discount)) {
                $discount = $this->prepare($discount, 'OnBeforeLoadCartForm');
                echo json_encode($discount);exit();
            }
        }
        return $responce;
    }

    public function ajaxWebuserGroupsList()
    {
        $groups = $this->WebuserGroupsList();
        return json_encode($groups);
    }
}

