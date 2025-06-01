
                        </div>
                        <div class="carousel-slide min-w-full">
                            <img src="https://images.unsplash.com/photo-1538805060514-97d9cc17730c?auto=format&fit=crop&w=1374&q=80"
                                 alt="Gym facility" class="w-full h-96 object-cover">
                        </div>
                    </div>
                    <button class="carousel-prev absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-70">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="carousel-next absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-70">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <div class="carousel-dots flex justify-center space-x-2 absolute bottom-4 left-0 right-0">
                        <button class="dot w-3 h-3 rounded-full bg-white bg-opacity-50 hover:bg-opacity-100" data-index="0"></button>
                        <button class="dot w-3 h-3 rounded-full bg-white bg-opacity-50 hover:bg-opacity-100" data-index="1"></button>
                        <button class="dot w-3 h-3 rounded-full bg-white bg-opacity-50 hover:bg-opacity-100" data-index="2"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- bulletin de la semaine -->
<section class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Bulletin de la semaine</h2>
            <p class="text-lg text-gray-600">Découvrez les dernières actualités et événements sportifs</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Carte 1 : Nouveau cours de yoga -->
            <div class="bg-gray-50 rounded-xl overflow-hidden shadow-md hover:shadow-lg transition-shadow">
                <div class="h-48 bg-blue-600 flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-white text-5xl"></i>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-semibold mb-2">Nouveau cours de yoga</h3>
                    <p class="text-gray-600 mb-4">Découvrez notre nouveau cours de yoga dynamique chaque mercredi à 18h.</p>
                    <a href="yoga.php" class="text-blue-600 font-medium hover:text-blue-800">En savoir plus</a>
                </div>
            </div>

            <!-- carte tournoi de basket -->
            <div class="bg-gray-50 rounded-xl overflow-hidden shadow-md hover:shadow-lg transition-shadow">
                <div class="h-48 bg-green-600 flex items-center justify-center">
                    <i class="fas fa-trophy text-white text-5xl"></i>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-semibold mb-2">Tournoi de basket</h3>
                    <p class="text-gray-600 mb-4">Inscrivez-vous dès maintenant pour le tournoi inter-campus du 15 juin.</p>
                    <a href="register.php" class="text-blue-600 font-medium hover:text-blue-800">S'inscrire</a>
                </div>
            </div>

            <!-- carte nouveaux coachs -->
            <div class="bg-gray-50 rounded-xl overflow-hidden shadow-md hover:shadow-lg transition-shadow">
                <div class="h-48 bg-purple-600 flex items-center justify-center">
                    <i class="fas fa-user-plus text-white text-5xl"></i>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-semibold mb-2">Nouveaux coachs</h3>
                    <p class="text-gray-600 mb-4">Découvrez les profils de nos 3 nouveaux coachs spécialisés en cross-training.</p>
                    <a href="#coaches" class="text-blue-600 font-medium hover:text-blue-800">Voir les profils</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- coach -->
<section id="coaches" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Nos coachs vedettes</h2>
            <p class="text-lg text-gray-600">Des professionnels qualifiés pour vous accompagner</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            // requete pour recup les coachs depuis la base
            $stmt = $pdo->prepare("
                SELECT c.id_coach, u.first_name, u.last_name, c.specialty, c.photo_path
                FROM coaches AS c
                INNER JOIN users AS u ON c.id_user = u.id_user
                ORDER BY u.last_name ASC
            ");
            $stmt->execute();
            $coachs = $stmt->fetchAll();

            if (!empty($coachs)):
                foreach ($coachs as $coach):
                    $photo = $coach['photo_path']
                        ? 'uploads/photos/' . htmlspecialchars($coach['photo_path'])
                        : 'https://via.placeholder.com/400x300?text=Coach';
            ?>
            <div class="bg-white rounded-xl overflow-hidden shadow-md coach-card transition-transform duration-300">
                <div class="relative">
                    <a href="coach.php?id=<?= $coach['id_coach'] ?>">
                        <img src="<?= $photo ?>"
                             alt="Photo de <?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?>"
                             class="w-full h-64 object-cover">
                    </a>
                </div>
                <div class="p-6">
                    <h3 class="text-2xl font-semibold mb-2">
                        <a href="coach.php?id=<?= $coach['id_coach'] ?>" class="hover:text-blue-600">
                            <?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?>
                        </a>
                    </h3>
                    <p class="text-gray-600 mb-4"><?= htmlspecialchars($coach['specialty']) ?></p>
                    <div class="flex justify-between items-center">
                        <a href="coach.php?id=<?= $coach['id_coach'] ?>"
                           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            Voir profil
                        </a>
                        <?php if (isLoggedIn() && getUserRole() === 'client'): ?>
                            <a href="disponibilites.php?coach_id=<?= $coach['id_coach'] ?>"
                               class="text-blue-600 font-medium hover:text-blue-800">
                                Réserver
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
                endforeach;
            else:
            ?>
            <p class="col-span-3 text-center text-gray-500">Aucun coach disponible pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
require_once 'footer.php';
?>
