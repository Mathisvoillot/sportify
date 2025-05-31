<?php
// footer.php
?>
    <!-- ===================== SCRIPTS JS COMMUNS ===================== -->
    <script>
        // Script minimal pour animer le carousel dans la page d’accueil (index.php).
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.carousel-slide');
            const prevBtn = document.querySelector('.carousel-prev');
            const nextBtn = document.querySelector('.carousel-next');
            const dots = document.querySelectorAll('.dot');
            let currentIndex = 0;

            function updateCarousel(index) {
                const container = document.querySelector('.carousel-slides');
                container.style.transform = `translateX(-${index * 100}%)`;
                dots.forEach(dot => dot.classList.remove('bg-opacity-100'));
                dots[index].classList.add('bg-opacity-100');
                currentIndex = index;
            }

            if (slides.length > 0) {
                prevBtn.addEventListener('click', () => {
                    let newIndex = currentIndex - 1;
                    if (newIndex < 0) newIndex = slides.length - 1;
                    updateCarousel(newIndex);
                });
                nextBtn.addEventListener('click', () => {
                    let newIndex = currentIndex + 1;
                    if (newIndex >= slides.length) newIndex = 0;
                    updateCarousel(newIndex);
                });
                dots.forEach(dot => {
                    dot.addEventListener('click', () => {
                        updateCarousel(parseInt(dot.getAttribute('data-index')));
                    });
                });
                // Initialisation
                updateCarousel(0);
            }
        });
    </script>
</body>
</html>
