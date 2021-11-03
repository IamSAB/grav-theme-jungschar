function scrollSpy(selector) {

  document.addEventListener("DOMContentLoaded", function () {

    var menu = document.querySelector(selector);
    var scrollElement = document.getElementById("scroll");

    function menuControl(e) {
      var links = menu.querySelectorAll('a[href^="#"]');
      var scrollPos = e.target.scrollTop;

      Array.from(links).forEach((link) => {
        const el = document.querySelector(link.getAttribute('href'));
        return el.offsetTop <= scrollPos && (el.offsetTop + el.clientHeight) > scrollPos
          ? link.classList.add('active')
          : link.classList.remove('active');
      });
    }
    
    scrollElement.addEventListener("scroll", menuControl);
  })
}