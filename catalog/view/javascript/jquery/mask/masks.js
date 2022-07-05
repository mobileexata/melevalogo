$(function () {
  $('input[name="telephone"]').mask('(00) 00000-0000');
  $('.cep').mask('00.000-000');
  $(document).on('blur', 'input[name="telephone"]', function () {
    if ($(this).val().replace(/\D/g, '') != "" && $(this).val().replace(/\D/g, '').length < 10) {
      alert("Telefone inválido!")
      $(this).val("")
    }
  });
});
