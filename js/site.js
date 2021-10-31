
var isTouch = window.DocumentTouch && document instanceof DocumentTouch;

function handleScroll(event) {
  var navbar = document.getElementById("navbar");
  if (event.target.scrollTop > 75)
    navbar.classList.add("scrolled");
  else
    navbar.classList.remove("scrolled");
}

document.addEventListener("DOMContentLoaded", function () {
  var scrollElement = document.getElementById("scroll");
  handleScroll({target: scrollElement});
  // Scroll Events
  if (!isTouch) {
    scrollElement.addEventListener('scroll', handleScroll);
  };
  // Touch scroll
  scrollElement.addEventListener("touchmove", handleScroll);
});
