document.addEventListener("DOMContentLoaded", function () {

  function handleScroll(e) {
    var navbar = document.getElementById("navbar");
    if (e.target.scrollTop > 75)
      navbar.classList.add("scrolled");
    else
      navbar.classList.remove("scrolled");
  }

  var scrollElement = document.getElementById("scroll");
  handleScroll({target: scrollElement});
  scrollElement.addEventListener("scroll", handleScroll);
});
