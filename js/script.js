
document.addEventListener('DOMContentLoaded', function () {

    const boutonMenu = document.querySelector('.menu-hamburger');
    const navigation = document.querySelector('.navigation-principale');

    if (boutonMenu && navigation) {

        boutonMenu.addEventListener('click', function () {

            navigation.classList.toggle('ouvert');

            const menuOuvert = navigation.classList.contains('ouvert');

            boutonMenu.setAttribute('aria-expanded', menuOuvert);

            boutonMenu.textContent = menuOuvert ? '✕ Fermer' : '☰ Menu';
        });

    }

});
