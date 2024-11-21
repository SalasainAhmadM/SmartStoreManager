// Get the button element
const scrollToTopBtn = document.getElementById("scrollToTopBtn");

// Show or hide the button depending on the scroll position
window.onscroll = function () {
  if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
    scrollToTopBtn.style.display = "block";
  } else {
    scrollToTopBtn.style.display = "none";
  }
};

// Add click event to scroll to the top
scrollToTopBtn.onclick = function () {
  window.scrollTo({
    top: 0,
    behavior: "smooth" // Smooth scrolling
  });
};
