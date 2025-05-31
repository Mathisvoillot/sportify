<?php
// header.php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sportify – Consultation Sportive en Ligne</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Si besoin de styles personnalisés, décommentez la ligne suivante :
    <link rel="stylesheet" href="css/custom.css" />
    -->
    <style>
        /* Vos styles personnalisés */
        .hero-gradient {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        }
        .coach-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .calendar-day {
            transition: all 0.2s ease;
        }
        .calendar-day:hover:not(.booked) {
            background-color: #3b82f6;
            color: white;
        }
        .chat-bubble {
            border-radius: 1rem;
            max-width: 70%;
        }
        .chat-bubble.user {
            border-bottom-right-radius: 0;
            background-color: #3b82f6;
            color: white;
        }
        .chat-bubble.coach {
            border-bottom-left-radius: 0;
            background-color: #e5e7eb;
        }
        #map {
            height: 400px;
            width: 100%;
        }
    </style>
</head>
<body class="font-sans bg-gray-50">

    <!-- ===================== NAVIGATION COMMUNE À TOUTES LES PAGES ===================== -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo & Liens principaux -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-dumbbell text-blue-600 text-2xl mr-2"></i>
                        <span class="text-xl font-bold text-blue-600">Sportify</span>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="index.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Accueil</a>
                        <a href="index.php#coaches" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Tout Parcourir</a>
                        <a href="search.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Recherche</a>
                        <?php if (isLoggedIn() && getUserRole() === 'client'): ?>
                            <a href="réserver.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Rendez-vous</a>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Boutons Connexion / Inscription ou Profil + Déconnexion -->
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <?php if (isLoggedIn()): ?>
                        <span class="text-gray-700 mr-4">Bonjour, <?= htmlspecialchars($_SESSION['user_first_name']) ?></span>
                        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-user mr-2"></i>Connexion
                        </a>
                        <a href="register.php" class="ml-4 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-user-plus mr-2"></i>Inscription
                        </a>
                    <?php endif; ?>
                </div>
                <!-- Bouton Menu Mobile -->
                <div class="-mr-2 flex items-center sm:hidden">
                    <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" id="mobile-menu-button">
                        <span class="sr-only">Ouvrir le menu principal</span>
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
        <!-- Menu Mobile (identique aux liens ci-dessus) -->
        <div class="hidden sm:hidden" id="mobile-menu">
            <div class="pt-2 pb-3 space-y-1">
                <a href="index.php" class="bg-blue-50 border-blue-500 text-blue-700 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Accueil</a>
                <a href="index.php#coaches" class="border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Tout Parcourir</a>
                <a href="search.php" class="border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Recherche</a>
                <?php if (isLoggedIn() && getUserRole() === 'client'): ?>
                    <a href="réserver.php" class="border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Rendez-vous</a>
                <?php endif; ?>
                <?php if (isLoggedIn()): ?>
                    <a href="logout.php" class="border-transparent text-red-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Déconnexion</a>
                <?php else: ?>
                    <a href="login.php" class="border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Connexion</a>
                    <a href="register.php" class="border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <!-- ===================== FIN DE LA NAVIGATION ===================== -->
<?php
// Fin de header.php – on est toujours dans <body>
