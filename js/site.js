
var isTouch = window.DocumentTouch && document instanceof DocumentTouch;

function setScrolledClass(event) {
  var navbar = document.getElementById("navbar");
  console.log(event.target.scrollTop);
  if (event.target.scrollTop > 75)
    navbar.classList.add("scrolled");
  else
    navbar.classList.remove("scrolled");
}

document.addEventListener("DOMContentLoaded", function () {
  var scrollElement = document.getElementById("scroll");
  setScrolledClass({target: scrollElement});
  // Scroll Events
  if (!isTouch) {
    scrollElement.addEventListener('scroll', setScrolledClass);
  };
  // Touch scroll
  scrollElement.addEventListener("touchmove", setScrolledClass);
});
