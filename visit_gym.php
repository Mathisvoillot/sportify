<?php
// visit_gym.php
require_once 'config.php';
require_once 'header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h2 class="text-3xl font-bold mb-6 text-center">Visiter la salle</h2>

    <p class="mb-6 text-gray-700">Bienvenue dans notre salle de sport Omnes Education ! Vous pouvez découvrir notre emplacement, nos équipements et nos horaires.</p>

    <!-- Exemple d'intégration d'une carte Google Maps (à remplacer par votre propre lien si besoin) -->
    <div class="w-full h-96 overflow-hidden rounded-lg shadow-lg mb-8">
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.9999999999995!2d2.2922926156749747!3d48.858373079287864!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66fc3e6f7b621%3A0x6f911cbe8eb7b613!2sTour%20Eiffel!5e0!3m2!1sfr!2sfr!4v1618997441669!5m2!1sfr!2sfr"
            width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy">
        </iframe>
    </div>

    <h3 class="text-2xl font-semibold mb-4">Horaires d’ouverture</h3>
    <ul class="list-disc pl-5 text-gray-700 mb-8">
        <li>Lundi – Vendredi : 6h00 – 22h00</li>
        <li>Samedi – Dimanche : 8h00 – 20h00</li>
    </ul>

    <h3 class="text-2xl font-semibold mb-4">Nos équipements</h3>
    <ul class="list-disc pl-5 text-gray-700 mb-8">
        <li>Zone cardio-training (tapis de course, vélo, rameur…)</li>
        <li>Musculation (haltères, machines guidées, cages à squat…)</li>
        <li>Espace cross-training et cours collectifs (yoga, Pilates…)</li>
        <li>Sauna & vestiaires</li>
    </ul>
</div>

<?php
require_once 'footer.php';
