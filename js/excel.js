// Scroll Button Logic
const scrollButton = document.getElementById('scrollButton');

// Function to handle scroll events
function handleScroll() {
    const scrollPosition = window.scrollY || window.pageYOffset;
    const windowHeight = window.innerHeight;
    const documentHeight = document.body.scrollHeight;

    if (scrollPosition + windowHeight >= documentHeight - 50) {
        scrollButton.style.bottom = '20px';
        scrollButton.style.top = 'auto';
        scrollButton.textContent = '↑'; // Change button text to an up arrow
        scrollButton.removeEventListener('click', scrollDown); // Remove the scroll-down listener
        scrollButton.addEventListener('click', scrollUp); // Add the scroll-up listener
    } else {
        scrollButton.style.bottom = 'auto';
        scrollButton.style.top = '20px';
        scrollButton.textContent = '↓'; // Change button text to a down arrow
        scrollButton.removeEventListener('click', scrollUp); // Remove the scroll-up listener
        scrollButton.addEventListener('click', scrollDown); // Add the scroll-down listener
    }
}

// Function to scroll down
function scrollDown() {
    window.scrollTo({
        top: document.body.scrollHeight,
        behavior: 'smooth'
    });
}

// Function to scroll up
function scrollUp() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Initial setup for the scroll button
handleScroll(); // Set the initial position of the button
scrollButton.addEventListener('click', () => {
    if (scrollButton.textContent === '↓') {
        scrollDown();
    } else {
        scrollUp();
    }
});

// Listen for scroll events to update the button position
window.addEventListener('scroll', handleScroll);