$(function () {
  $('input[name="telephone"]').mask('(00) 0000-00000');
  $('.cep').mask('00.000-000');
});

window.onload = function () {
  if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
    $('.owl-item').each(function () {
      const width = $(this).css('width')
      const divWidth = parseInt(width) / 2
      $(this).css('width', divWidth.toString() + 'px')
    });
  }
}