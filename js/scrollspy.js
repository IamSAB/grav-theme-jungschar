function scrollSpy(selector) {

  document.addEventListener("DOMContentLoaded", function () {

    var menu = document.querySelector(selector);
    var scrollElement = document.getElementById("scroll");
    console.log(selector);

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

    var isTouch = window.DocumentTouch && document instanceof DocumentTouch;
    if (!isTouch) scrollElement.addEventListener('scroll', menuControl);
    scrollElement.addEventListener("touchmove", menuControl);
  })
}