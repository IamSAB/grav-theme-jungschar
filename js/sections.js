scrollSpy('#scrollspy-sections');
scrollSpy('#scrollspy-sections-drawer');

// close drawer if links are clicked
document.addEventListener("DOMContentLoaded", function () {
  var links = document.querySelectorAll('#scrollspy-sections-drawer a');
  var input = document.querySelector('#menu-drawer');
  Array.from(links).forEach((link) => {
    link.addEventListener('click', function () {
      input.checked = false;
    });
  });
});