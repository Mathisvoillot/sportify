<?php
// yoga.php
require_once 'config.php';
require_once 'header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h2 class="text-3xl font-bold mb-6 text-center">Cours de Yoga Dynamique</h2>

    <div class="space-y-6 text-gray-700">
        <p>Notre <strong>cours de yoga dynamique</strong> se déroule chaque mercredi à 18 h, dans la salle polyvalente du campus Omnes.</p>
        <p><strong>Professeur :</strong> <em>Emma Laroche</em> – coach certifiée RYT 200 & 500.</p>
        <p>Ce cours est adapté à tous les niveaux : débutants, intermédiaires, ou pratiquants avancés. Au programme :</p>
        <ul class="list-disc pl-5">
            <li>Échauffement respiratoire et mouvements fluides</li>
            <li>Séquences de postures dynamiques (Vinyasa Flow)</li>
            <li>Travail d’ouverture des hanches et de la colonne</li>
            <li>Relaxation finale et méditation guidée</li>
        </ul>
        <p><strong>Lieu :</strong> Salle Polyvalente, campus Omnes – Bâtiment A, 2e étage.</p>
        <p><strong>Tarif :</strong> 10 € la séance ou 80 € le forfait 10 cours. Réservation obligatoire.</p>
        <p>Pour toute question, contactez-nous à <a href="mailto:sportify@yoga.example.com" class="text-blue-600 hover:underline">sportify@yoga.example.com</a> ou via le chat en ligne.</p>
        <div class="mt-8 text-center">
            <a href="register.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium">
                S'inscrire au cours
            </a>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
