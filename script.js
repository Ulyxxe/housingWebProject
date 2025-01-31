//script.js
document.querySelectorAll('button').forEach(button => {
    button.addEventListener('click', event => {
        event.preventDefault();
        const target = event.target.getAttribute('href') || event.target.getAttribute('onclick').split("'")[1];
        document.getElementById(target).scrollIntoView({ behavior: 'smooth' });
    });
});