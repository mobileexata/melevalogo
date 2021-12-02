$(document).ready(function() {
  $(document).on('contextmenu', function(e) {
    return false;
  });

  $(document).on('cut copy paste', function(e) {
    e.preventDefault();
  });

  $('#payment select[name="bandeira"]').on('change', function() {
    parcelas($(this).val());
  });

  $('#payment input[type="text"][name="cartao"]').on('keyup change', function() {
    $(this).val($(this).val().replace(/[^\d]/g,''));
  });

  $('#payment input[type="text"][name="codigo"]').on('keyup change', function() {
    $(this).val($(this).val().replace(/[^\d]/g,''));
  });

  $('#payment input[type="text"][name="documento"]').on('keyup change', function() {
    $(this).val($(this).val().replace(/[^\d]/g,''));
  });

  $('#payment input[type="text"], #payment select').on('blur change', function() {
    if ($.trim($(this).val()).length > 0) {
      validar();
    }
  });

  $('#button-confirm').click(function(e) {
    e.preventDefault();

    if (validar()) {
      transacao();
    }
  });
});