Ext.provide('Phlexible.suggest.field.SuggestField');

Ext.require('Ext.ux.form.SuperBoxSelect');

Phlexible.suggest.field.SuggestField = Ext.extend(Ext.ux.form.SuperBoxSelect, {
    onResize: function (w, h, rw, rh) {
        Phlexible.suggest.field.SuggestField.superclass.onResize.call(this, w, h, rw, rh);

        this.wrap.setWidth(this.width + 20);
    }
});
Ext.reg('suggest-field-suggest', Phlexible.suggest.field.SuggestField);
