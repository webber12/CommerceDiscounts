<!DOCTYPE HTML>
<html>
    <head>
    <link rel="stylesheet" href="media/style/common/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.materialdesignicons.com/4.5.95/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="https://cdn.webix.com/edge/webix.min.css" type="text/css">
    <link rel="stylesheet" href="[+module_url+]skin/skin.css" type="text/css">
    <style>
        body.webix_full_screen{overflow:auto !important;}
        /*.webix_view.webix_pager{margin-bottom:30px;}
        .webix_cell{-webkit-transition: all .3s,-moz-transition: all .3s,-o-transition: all .3s,transition: all .3s}
        .webix_cell:nth-child(odd){background-color:#f6f8f8;}
        .webix_cell:hover{background-color: rgba(93, 109, 202, 0.16);}*/
    </style>
    <script src="https://cdn.webix.com/edge/webix.min.js" type="text/javascript"></script>
    <script src="//cdn.webix.com/site/i18n/ru.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript" src="https://cdn.webix.com/components/edge/ckeditor5/ckeditor5.js"></script>
    </head>
    <body class="[+manager_theme_mode+]">
        <div id="wbx_table" style="padding-bottom:20px;"></div>
        <div id="wbx_pp" style="padding-bottom:20px;width:90%;"></div>
    
        <script type="text/javascript" charset="utf-8">

        webix.ready(function() {
            webix.i18n.setLocale("ru-RU");
            webix.attachEvent("onBeforeAjax", 
                function(mode, url, data, request, headers, files, promise){
                    headers["X-Requested-With"] = "XMLHttpRequest";
                }
            );
            webix.editors.$popup = {
                date:{
                    view:"popup",
                    body:{ 
                        view:"calendar", 
                        /*timepicker:true, 
                        timepickerHeight:50,*/
                        width: 320, 
                        height:300,
                        calendarDateFormat: "%Y-%m-%d"
                    }
                },
                text:{
                    view:"popup", 
                    body:{view:"textarea", width:350, height:150}
                }
            };

            
            var category_form = {
                view:"form",
                id:"category_form",
                borderless:true,
                elements: [
                    {rows:[
                        {view:"text", label:"Название скидки", name:"name"},
                        {template:"Элементы", type:"section"},
                        {view:"list", id:"category_list", template:"#title#", autoheight:true}
                    ]},
                    {rows:[
                        {view:"button", type:"icon", icon:"mdi mdi-pine-tree",  label:"Показать дерево категорий", autoWidth:true, css:"webix_primary", click:show_category_tree},
                    ]},
                    
                    {rows : [
                        {template:"Размер скидки", type:"section"}
                    ]},
                    {view:"text", label:"Скидка %", name:"discount", pattern:{ mask:"##", allow:/[0-9]/g}, value:"1", min:"1"},
                    {view:"text", label:"Скидка руб (если не заполнена скидка в %)", name:"discount_summ", pattern:{ mask:"####", allow:/[0-9]/g}, min:"1"},
                    
                    {rows : [
                        {template:"Условия применения", type:"section"}
                    ]},
                    { margin:5, cols:[
                        {view:"datepicker", label:"Действует с", name:"date_start"},
                        {view:"datepicker", label:"Действует по", name:"date_finish"},
                    ]},
                    {view:"select", label:"Группа пользователей", name:"user_group", options:"[+module_url+]action.php?action=WebuserGroupsList&module_id=[+module_id+]"},
                    { margin:5, cols:[
                        {view:"select", label:"", name:"condition_type", options:[{id:1, value:"для каждого"}, {id:2, value:"начиная с"} ]},
                        {view:"counter", label:"", name:"condition_count", min:"1", value:"1", css:"wide_counter"},
                        {view:"select", label:"", name:"condition_item", options:[{id:1, value:"шт"}]},
                    ]},


                    {view:"checkbox", label:"Активен", name:"active", value:"1"},
                    { margin:5, cols:[
                        { view:"button", value: "Сохранить", type:"form", css:"webix_primary", click:function(id,event){var discount_id = $$("mydatatable").getSelectedId();save_category_form(discount_id);}},
                        { view:"button", value: "Закрыть", click: function (elementId, event) {this.getTopParentView().hide();}}
                    ]},
                    {rows : [
                        {template:"The End", type:"section"}
                    ]}
                ],
                rules:{},
                elementsConfig:{
                    labelPosition:"top",
                },
                height:500,
                scroll:"y"
            };
            
            var product_form = {
                view:"form",
                id:"product_form",
                borderless:true,
                elements: [
                    {rows:[
                        {view:"text", label:"Название скидки", name:"name"},
                        {template:"Элементы", type:"section"},
                        {view:"list", id:"product_list", template:"#title#", autoheight:true}
                    ]},
                    {rows:[
                        {view:"button", type:"icon", icon:"mdi mdi-pine-tree",  label:"Показать список товаров", autoWidth:true, css:"webix_primary", click:show_product_tree},
                    ]},
                    {rows : [
                        {template:"Размер скидки", type:"section"}
                    ]},
                    {view:"text", label:"Скидка %", name:"discount", pattern:{ mask:"##", allow:/[0-9]/g}, value:"1", min:"1"},
                    {view:"text", label:"Скидка руб (если не заполнена скидка в %)", name:"discount_summ", pattern:{ mask:"####", allow:/[0-9]/g}, min:"1"},
                    
                    {rows : [
                        {template:"Условия применения", type:"section"}
                    ]},
                    { margin:5, cols:[
                        {view:"datepicker", label:"Действует с", name:"date_start"},
                        {view:"datepicker", label:"Действует по", name:"date_finish"},
                    ]},
                    {view:"select", label:"Группа пользователей", name:"user_group", options:"[+module_url+]action.php?action=WebuserGroupsList&module_id=[+module_id+]"},
                    { margin:5, cols:[
                        {view:"select", label:"", name:"condition_type", options:[{id:1, value:"для каждого"}, {id:2, value:"начиная с"} ]},
                        {view:"counter", label:"", name:"condition_count", min:"1", value:"1", css:"wide_counter"},
                        {view:"select", label:"", name:"condition_item", options:[{id:1, value:"шт"}, {id:2, value:"руб"}], value:"1"},
                    ]},

                    {view:"checkbox", label:"Активен", name:"active", value:"1"},
                    { margin:5, cols:[
                        { view:"button", value: "Сохранить", type:"form", css:"webix_primary", click:function(id,event){var discount_id = $$("mydatatable").getSelectedId();save_product_form(discount_id);}},
                        { view:"button", value: "Закрыть", click: function (elementId, event) {this.getTopParentView().hide();}}
                    ]},
                    {rows : [
                        {template:"The End", type:"section"}
                    ]}
                ],
                rules:{},
                elementsConfig:{
                    labelPosition:"top",
                },
                height:500,
                scroll:"y"
            };
            
            var cart_form = {
                view:"form",
                id:"cart_form",
                borderless:true,
                elements: [
                    {rows:[
                        {view:"text", label:"Название скидки", name:"name"},
                    ]},

                    {rows : [
                        {template:"Размер скидки", type:"section"}
                    ]},
                    {view:"text", label:"Скидка %", name:"discount", pattern:{ mask:"##", allow:/[0-9]/g}, value:"1", min:"1"},
                    {view:"text", label:"Скидка руб (если не заполнена скидка в %)", name:"discount_summ", pattern:{ mask:"####", allow:/[0-9]/g}, min:"1"},

                    {rows : [
                        {template:"Условия применения", type:"section"}
                    ]},
                    { margin:5, cols:[
                        {view:"datepicker", label:"Действует с", name:"date_start"},
                        {view:"datepicker", label:"Действует по", name:"date_finish"},
                    ]},
                    {view:"select", label:"Группа пользователей", name:"user_group", options:"[+module_url+]action.php?action=WebuserGroupsList&module_id=[+module_id+]"},
                    { margin:5, cols:[
                        {view:"select", label:"", name:"condition_type", options:[{id:2, value:"начиная с"} ]},
                        {view:"counter", label:"", name:"condition_count", min:"0", value:"0", css:"wide_counter"},
                        {view:"select", label:"", name:"condition_item", options:[{id:1, value:"шт"}, {id:2, value:"руб"}], value:2},
                    ]},
                    {view:"checkbox", label:"Исключать акционные товары", name:"exclude_sales", value:"1"},
                    {view:"checkbox", label:"Активен", name:"active", value:"1"},
                    { margin:5, cols:[
                        { view:"button", value: "Сохранить", type:"form", css:"webix_primary", click:function(id,event){var discount_id = $$("mydatatable").getSelectedId();save_cart_form(discount_id);}},
                        { view:"button", value: "Закрыть", click: function (elementId, event) {this.getTopParentView().hide();}}
                    ]},
                    {rows : [
                        {template:"The End", type:"section"}
                    ]}
                ],
                rules:{},
                elementsConfig:{
                    labelPosition:"top",
                },
                height:500,
                scroll:"y"
            };
            
            webix.ui({
                view:"window",
                id:"win_category",
                move:true,
                width:500,
                height:500,
                position:"center",
                modal:true,
                head:{
                    view:"toolbar", margin:5, cols:[
                        {view:"label", label: "Скидки для категорий товаров" },
                        {view:"icon", icon:"wxi-check", click:function(id,event){var discount_id = $$("mydatatable").getSelectedId();save_category_form(discount_id);}, tooltip:"Сохранить изменения"},
                        {view:"icon", icon:"wxi-close", click:"$$('win_category').hide();", tooltip:"Закрыть без сохранения"}
                        ]
                },
                body: category_form
            });
            
            webix.ui({
                view:"window",
                id:"win_category_tree",
                move:true,
                width:500,
                height:500,
                position:"center",
                modal:true,
                head:{
                    view:"toolbar", margin:5, cols:[
                        {view:"label", label: "Дерево категорий" },
                        {view:"icon", icon:"wxi-check", click:function(id,event){var discount_id = $$("mydatatable").getSelectedId();save_category_tree(discount_id);}, tooltip:"Сохранить изменения"},
                        {view:"icon", icon:"wxi-close", click:"$$('win_category_tree').hide();", tooltip:"Закрыть без сохранения"}
                        ]
                },
                body:{
                    rows:[
                    {cols:[
                        { view:"button", type:"icon", icon:"mdi mdi-file-tree",  label:"Раскрыть все", width:160, css:"webix_primary", click:function(id,event){$$("category_tree").openAll()} },
                        { view:"button", type:"icon", icon:"mdi mdi-pine-tree",  label:"Свернуть дерево", width:160, css:"webix_primary", click:function(id,event){$$("category_tree").closeAll()} },
                    ]},
                    {
                        view:"treetable",
                        label:"Категории",
                        id: "category_tree",
                        data:[{id:"1", value:"Каталог"}],
                        autoheight:true,
                        autowidth:true,
                        minHeight: 300,
                        columns:[
                            { id:"id", header:"ID", css:{"text-align":"left"}, width:40},
                            { id:"value", header:"Категории каталога", width:400, template:"{common.space()}{common.icon()}{common.treecheckbox()}{common.folder()}#value#" }
                        ],
                        activeTitle:true
                    },
                    {cols:[
                        { view:"button", value: "Сохранить", type:"form", css:"webix_primary", click:function(id,event){var discount_id = $$("mydatatable").getSelectedId();save_category_tree(discount_id);}},
                        { view:"button", value: "Закрыть", click: function (id,event) {this.getTopParentView().hide();}}
                    ]},
                    {rows : [
                        {template:"The End", type:"section"}
                    ]}
                ]},
            });
            
            webix.ui({
                view:"window",
                id:"win_product",
                move:true,
                width:500,
                height:500,
                position:"center",
                modal:true,
                head:{
                    view:"toolbar", margin:5, cols:[
                        {view:"label", label: "Скидки для товаров" },
                        {view:"icon", icon:"wxi-check", click:function(id,event){var discount_id = $$("mydatatable").getSelectedId();save_product_form(discount_id);}, tooltip:"Сохранить изменения"},
                        {view:"icon", icon:"wxi-close", click:"$$('win_product').hide();", tooltip:"Закрыть без сохранения"}
                        ]
                },
                body: product_form
            });
            
            webix.ui({
                view:"window",
                id:"win_product_tree",
                move:true,
                width:500,
                height:500,
                position:"center",
                modal:true,
                head:{
                    view:"toolbar", margin:5, cols:[
                        {view:"label", label: "Список товаров" },
                        {view:"icon", icon:"wxi-check", click:function(id,event){var discount_id = $$("mydatatable").getSelectedId();save_product_tree(discount_id);}, tooltip:"Сохранить изменения"},
                        {view:"icon", icon:"wxi-close", click:"$$('win_product_tree').hide();", tooltip:"Закрыть без сохранения"}
                        ]
                },
                body:{
                    rows:[
                    {cols:[
                        { view:"toolbar", cols:[
                            { view:"text", css:"search_product_tree_field", id:"search_product_tree_field", placeholder:"Фильтр по названию"},
                            { view:"button", type:"icon", css:"search_product_tree_btn", icon:"mdi mdi-filter", label:"", tooltip:"Искать", autowidth:true, click:function(id,event){var discount_id = $$("mydatatable").getSelectedId();search_product_tree(discount_id);}}
                        ]},
                        { view:"toolbar", cols:[
                            {view:"template", template: "Товары со скидкой", height:40, css:"product_tree_selected_title" },
                        ]},
                    ]},
                    {cols:[
                    {
                        view:"list",
                        label:"Список товаров",
                        id: "product_tree",
                        data:[],
                        autowidth:true,
                        maxHeight: 400,
                        drag:"move"
                    },
                    {
                        view:"list",
                        label:"Отобранные товары",
                        id: "product_tree_selected",
                        data:[],
                        autowidth:true,
                        maxHeight: 400,
                        drag:"move"
                    },
                    ]},
                    {cols:[
                        { view:"button", value: "Сохранить", type:"form", css:"webix_primary", click:function(id,event){var discount_id = $$("mydatatable").getSelectedId();save_product_tree(discount_id);}},
                        { view:"button", value: "Закрыть", click: function (id,event) {this.getTopParentView().hide();}}
                    ]},
                    {rows : [
                        {template:"The End", type:"section"}
                    ]}
                ]},
            });

            webix.ui({
                view:"window",
                id:"win_cart",
                move:true,
                width:500,
                height:500,
                position:"center",
                modal:true,
                head:{
                    view:"toolbar", margin:5, cols:[
                        {view:"label", label: "Скидки для корзины" },
                        {view:"icon", icon:"wxi-check", click:function(id,event){var discount_id = $$("mydatatable").getSelectedId();save_cart_form(discount_id);}, tooltip:"Сохранить изменения"},
                        {view:"icon", icon:"wxi-close", click:"$$('win_cart').hide();", tooltip:"Закрыть без сохранения"}
                        ]
                },
                body: cart_form
            });
            
            webix.ui({
                container:"wbx_table",
                rows:[
                    { view:"template", type:"header", template:"[+name+]"},
                    { view:"toolbar", id:"mybar", elements:[
                        { view:"button", type:"icon", id:"discount_1", icon:"mdi mdi-folder-open", label:"", tooltip:"Создать скидки для категорий", autowidth:true, click:function(id,event){create_discount(id)}},
                        { view:"button", type:"icon", id:"discount_2", icon:"mdi mdi-format-list-bulleted", label:"", tooltip:"Создать скидки для товаров", autowidth:true, click:function(id,event){create_discount(id)}},
                        { view:"button", type:"icon", id:"discount_4", icon:"mdi mdi-cart", label:"", tooltip:"Создать скидки для корзины", autowidth:true, click:function(id,event){create_discount(id)}},
                        [+modal_edit_btn+]
                        { view:"button", type:"icon", icon:"wxi-radiobox-blank", label:"", tooltip:"Перегрузить данные", click:"reload", autowidth:true },
                        { view:"button", type:"icon", icon:"wxi-trash",  label:"Удалить", width:110, css:"webix_danger", click:"del_row" },
                        ]
                    },
                    [+add_search_form+]
                    { view:"datatable",
                        autoheight:true,select:"row",
                        resizeColumn:true,
                        id:"mydatatable",
                        editable:[+inline_edit+],
                        editaction: "dblclick",
                        datafetch:[+display+],
                        navigation:true,
                        columns : [+cols+] ,
                        pager:{   
                            size : [+display+],
                            group : 5,
                            template : "{common.first()} {common.prev()} {common.pages()} {common.next()} {common.last()}",
                            container:"wbx_pp"
                        },
                        url: "[+module_url+]action.php?action=List&module_id=[+module_id+]",
                        save: "[+module_url+]action.php?action=Update&module_id=[+module_id+]",
                        delete: "[+module_url+]action.php?action=Delete&module_id=[+module_id+]",
                        /*ready:function(){
                            this.editColumn("discount_type");
                            this.attachEvent("onBeforeEditStop", function(){ return false; });
                            this.attachEvent("onEditorChange", function(id, value){
                              this.getItem(id.row)[id.column] = value;
                              this.refresh(id.row);
                            });
                        },*/
                        on:{
                            onAfterEditStart: function(){
                              var inp = this.getEditor().getInputNode();
                              if(inp.tagName == "SELECT" && document.createEvent){
                                var clickEvent = document.createEvent('MouseEvents');
                                clickEvent.initEvent('mousedown', true, true);
                                inp.dispatchEvent(clickEvent);
                              }
                            },
                            onEditorChange: function(id, value) {
                                if (id.column == 'discount_type' || id.column == 'user_group') {
                                    //меняем значение селекта на его id для сохранения
                                    this.getItem(id.row)[id.column] = id;
                                    this.refresh(id.row);
                                }
                            }
                        },
                    }
                ]
            });
            webix.ui({
                view:"contextmenu",
                id:"cmenu",
                data:[[+context_edit_btn+]"Удалить"],
                on:{
                    onItemClick:function(id){
                        var action = this.getItem(id).value;
                        switch (action) {
                            case 'Удалить':
                                del_row();
                                break;
                            case 'Правка':
                                edit_row();
                                break;
                            default:break;
                        }
                    }
                }
            });
            $$("cmenu").attachTo($$("mydatatable"));
        });

        function add_row() {
            webix.ajax('[+module_url+]action.php?action=GetNext&module_id=[+module_id+]').then(function(data){
                var data = data.json();
                if (typeof data.max != "undefined") {
                    var ins = {'[+idField+]' : data.max};
                    $$("mydatatable").add(ins, 0).refresh();
                }
            });
        }
        function del_row() {
            var selected = $$("mydatatable").getSelectedId();
            if (typeof(selected) !== "undefined") {
                webix.confirm("Вы уверены, что хотите удалить выбранную строку?", "confirm-warning", function(result){
                    if (result === true) {
                        $$("mydatatable").remove(selected);
                    }
                });
            } else {
                show_alert("Вы не выбрали строку для удаления", "alert-warning");
            }
        }
        function resetTable() {
            $$("mydatatable").eachColumn( function(pCol) { var f = this.getFilter(pCol); if (f) if (f.value) f.value = ""; });
            $$("mydatatable").clearAll();
            $$("mydatatable").setState({});
            $$("mydatatable").load($$("mydatatable").config.url);
        }
        function refresh(str = '') {
            $$("mydatatable").clearAll();
            $$("mydatatable").load($$("mydatatable").config.url + str, "json", refreshState);
        }
        function refreshState() {
            var mydatatable_state = webix.storage.local.get("mydatatable_state");
            if (mydatatable_state) {$$("mydatatable").setState(mydatatable_state);}
        }
        function edit_row(){
            var selected = $$("mydatatable").getSelectedItem();
            if (typeof(selected) !== "undefined") {
                var discount_type = selected.discount_type;
                switch (discount_type) {
                    case '1'://categories
                        $$("win_category").show();
                        load_category_form(selected.id);
                        break;
                    case '2': //products
                        $$("win_product").show();
                        load_product_form(selected.id);
                        break;
                    case '4': //cart
                        $$("win_cart").show();
                        load_cart_form(selected.id);
                        break;
                }
            } else {
                show_alert("Вы не выбрали строку для редактирования", "alert-warning");
            }
        }
        
        
        function submit_form() {
            var mydatatable_state = $$("mydatatable").getState();
            webix.storage.local.put("mydatatable_state", mydatatable_state);
            webix.ajax().post("[+module_url+]action.php?action=UpdateRow&module_id=[+module_id+]", $$("myform").getValues(), function(text, data, xhr){ 
                if (text == 'ok') {
                    var selected = $$("mydatatable").getSelectedId();
                    webix.ajax("[+module_url+]action.php?action=GetRow&module_id=[+module_id+]&key=" + selected).then(function(data){
                        var data = data.json();
                        var item = $$("mydatatable").getItem(selected);
                        for (k in item) {
                            if (k != 'id') {
                                item[k] = data[k];
                            }
                        }
                        $$("mydatatable").refresh();
                    });
                    //refresh();
                    show_alert('Изменения успешно сохранены', "alert-success");
                } else {
                    show_alert('Ошибка на сервере, попробуйте позднее', "alert-warning");
                }
            });
        }
        function add_search() {
            var obj = $$("searchform").getValues();
            var str = '';
            for (key in obj) {
                str = str + '&' + key + '=' + obj[key];
            }
            refresh(str);
        }
        function show_alert(text, level) {
            webix.alert(text, level, function(result){});
        }
        function reload() {
            document.location.reload(true);
        }
        function create_discount(type) {
            var ins = {'[+idField+]': -1, discount_type: type.replace('discount_', '')};
            $$("mydatatable").add(ins, 0);
            reload();
        }
        function show_category_tree() {
            var discount_id = $$("mydatatable").getSelectedId();
            $$("win_category_tree").show();
            load_category_tree(discount_id);
        }
        function load_category_tree(discount_id) {
            $$("category_tree").clearAll();
            $$("category_tree").load("[+module_url+]action.php?action=LoadCategoryTree&module_id=[+module_id+]&id=" + discount_id).then(function(data){
                var checked = $$("category_tree").getChecked();
                for (k in checked) {
                    $$("category_tree").open(checked[k]);
                }
            });
        }
        function load_category_list(discount_id) {
            $$("category_list").clearAll();
            $$("category_list").load("[+module_url+]action.php?action=LoadCategoryList&module_id=[+module_id+]&id=" + discount_id);
        }
        function save_category_tree(discount_id) {
            webix.ajax().post("[+module_url+]action.php?action=SaveCategoryTree&module_id=[+module_id+]&id=" + discount_id, {"checked": $$("category_tree").getChecked()}, function(text, data, xhr){ 
                if (text == 'ok') {
                    show_alert('Выбранные категории успешно сохранены', "alert-success");
                    load_category_list(discount_id);
                } else {
                    show_alert('Ошибка на сервере, попробуйте позднее', "alert-warning");
                }
            });
        }
        function load_category_form(discount_id) {
            $$("category_form").load("[+module_url+]action.php?action=LoadCategoryForm&module_id=[+module_id+]&id=" + discount_id).then(function(data){data = data.json();load_category_list(data.id)});
        }
        
        function show_product_tree() {
            var discount_id = $$("mydatatable").getSelectedId();
            $$("win_product_tree").show();
            load_product_tree(discount_id);
        }
        
        function load_product_form(discount_id) {
            $$("product_form").load("[+module_url+]action.php?action=LoadProductForm&module_id=[+module_id+]&id=" + discount_id).then(function(data){data = data.json();load_product_list(data.id)});
        }
        
        function load_product_list(discount_id) {
            $$("product_list").clearAll();
            $$("product_list").load("[+module_url+]action.php?action=LoadProductList&module_id=[+module_id+]&id=" + discount_id);
        }
        function load_product_tree(discount_id) {
            $$("product_tree").clearAll();
            $$("product_tree_selected").clearAll();
            $$("product_tree_selected").load("[+module_url+]action.php?action=LoadProductTree&selected=1&module_id=[+module_id+]&id=" + discount_id);
        }
        function search_product_tree(discount_id) {
            $$("product_tree_selected").selectAll();
            var ids = $$("product_tree_selected").getSelectedId();
            $$("product_tree_selected").unselectAll();
            var search = $$("search_product_tree_field").getValue();
            $$("product_tree").clearAll();
            $$("product_tree").load("[+module_url+]action.php?action=LoadProductTree&module_id=[+module_id+]&id=" + discount_id + "&checked=" + ids + "&search=" + search);

        }
        function save_product_tree(discount_id) {
            $$("product_tree_selected").selectAll();
            var ids = $$("product_tree_selected").getSelectedId();
            $$("product_tree_selected").unselectAll();
            //alert(ids);
            webix.ajax().post("[+module_url+]action.php?action=SaveProductTree&module_id=[+module_id+]&id=" + discount_id, {"checked": ids}, function(text, data, xhr){ 
                if (text == 'ok') {
                    show_alert('Выбранные товары успешно сохранены', "alert-success");
                    load_product_list(discount_id);
                } else {
                    show_alert('Ошибка на сервере, попробуйте позднее', "alert-warning");
                }
            });
        }
        function load_cart_form(discount_id) {
            $$("cart_form").load("[+module_url+]action.php?action=LoadCartForm&module_id=[+module_id+]&id=" + discount_id);
        }
        function save_category_form(discount_id) {
            webix.ajax().post("[+module_url+]action.php?action=SaveCategoryForm&module_id=[+module_id+]&id=" + discount_id, $$("category_form").getValues(), function(text, data, xhr){ 
                if (text == 'ok') {
                    show_alert('Изменения успешно сохранены', "alert-success");
                    refresh_table_row();
                } else {
                    show_alert('Ошибка на сервере, попробуйте позднее', "alert-warning");
                }
            });
        }
        function save_product_form(discount_id) {
            webix.ajax().post("[+module_url+]action.php?action=SaveProductForm&module_id=[+module_id+]&id=" + discount_id, $$("product_form").getValues(), function(text, data, xhr){ 
                if (text == 'ok') {
                    show_alert('Изменения успешно сохранены', "alert-success");
                    refresh_table_row();
                } else {
                    show_alert('Ошибка на сервере, попробуйте позднее', "alert-warning");
                }
            });
        }
        function save_cart_form(discount_id) {
            webix.ajax().post("[+module_url+]action.php?action=SaveCartForm&module_id=[+module_id+]&id=" + discount_id, $$("cart_form").getValues(), function(text, data, xhr){ 
                if (text == 'ok') {
                    show_alert('Изменения успешно сохранены', "alert-success");
                    refresh_table_row();
                } else {
                    show_alert('Ошибка на сервере, попробуйте позднее', "alert-warning");
                }
            });
        }
        function refresh_table_row() {
            var selected = $$("mydatatable").getSelectedId();
            webix.ajax("[+module_url+]action.php?action=GetRow&module_id=[+module_id+]&key=" + selected).then(function(data){
                var data = data.json();
                var item = $$("mydatatable").getItem(selected);
                for (k in item) {
                    if (k != 'id') {
                        item[k] = data[k];
                    }
                }
                $$("mydatatable").refresh();
            });
        }
        </script>
    </body>
</html>