(function ($) {
    $(document).on('click', '.jet-form-editor__buttons  ', function () {
        payamito_jet_dynamic_fileds();
    });
    $(document).on('click', '.jet-form-canvas__field-remove', function () {
        payamito_jet_dynamic_fileds();
    });
    $(document).on('click', '.jet-form-canvas__add', function () {
        payamito_jet_dynamic_fileds();
    });

    function payamito_jet_dynamic_fileds() {

        var result = JEBookingFormBuilder.result
        var $options = [];

        for (const field of result) {
            let name = field.settings.name;
            let label = field.settings.label;
            let type = field.settings.type;
            $options.push({ 'name': name, 'label': label, 'type': type });
        }

        $select = $('[data-options="dynamic"]');
        for (const select of $select) {
            let selected = $(select).find(":selected").val();
            $(select).empty();
            for (const option of $options) {
                let is_field = $(select).data("field");
                if (typeof is_field === 'undefined') {

                    if (option.type !== 'submit') {
                        if (option['name'] === selected) {
                         
                            $(select).append('<option value=' + option.type + '|' + option['name'] + ' selected="">' + option['label'] + '</option>');
                        } else {
                            $(select).append('<option value=' + option.type + '|' + option['name'] + ' >' + option['label'] + '</option>');
                        }
                    }
                }
                if (is_field === 'field') {
                    if (option.type == 'text' || option.type == 'number') {
                        if (option['name'] === selected) {
                            $(select).append('<option value=' + option['name'] + ' selected="">' + option['label'] + '</option>');
                        } else {
                            $(select).append('<option value=' + option['name'] + ' >' + option['label'] + '</option>');
                        }
                    }
                }
            }
        }
    }
    payamito_jet_dynamic_fileds();
    $('[data-copy-user-meta="copy"]').change(function (e, selected) {
       
        let target = $(e.target);
        let dataID = target.data("copy-texarea");
        let texarea = $('[data-texarea=' + dataID + ']');
        let value=texarea.val();
        texarea.val(value+ "" + selected.selected);
    });
})(jQuery)

