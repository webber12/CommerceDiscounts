var CommerceDiscounts = {
    bind: function() {
        var self = this;
        $(document).on("click change", "[data-commerce-action-discount]", function(e){;
            e.preventDefault();
            self.makeAction($(this), e);
        })
    },
    makeAction: function(el, event){
        var action = el.attr('data-commerce-action-discount'),
            row    = el.attr('data-commerce-row') || el.closest('[data-commerce-row]').attr('data-commerce-row'),
            data   = el.serializeDataAttributes(),
            hash   = el.closest('[data-commerce-cart]').attr('data-commerce-cart');
            if (event.type == 'change' && action == 'recount') {
                this.sendRequest({'count': el.val(), 'row': row, 'action': action, 'hash': hash});
            }
            if (event.type == 'click' && action == 'remove') {
                this.sendRequest({'row': row, 'action': action, 'hash': hash});
            }
    },
    sendRequest: function(obj) {
        var self = this;
        var data = '';
        for (k in obj) {
            data += '&' + k + '=' + obj[k];
        }
        $.ajax({
            url: 'cart/update/discount',
            data: data,
            type: "POST",
            cache: false,
            dataType: 'json',
            beforeSend:function(){
                //form_loader.show();
            },
            success: function(msg){
                var responce = "commerce-discounts-" + msg.status;
                $(document).trigger(responce);
                console.log(responce);
            }
        })
    }
}
/* events
// commerce-discounts-recount-discount-ok
// commerce-discounts-remove-discount-ok
*/
$(document).ready(function(){

    CommerceDiscounts.bind();

    $(document).on("commerce-discounts-recount-discount-ok", function(){
        Commerce.reloadCarts();
    })
    
    $(document).on("commerce-discounts-remove-discount-ok", function(){
        Commerce.reloadCarts();
    })

})
